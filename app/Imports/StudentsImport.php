<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\ImportedStudentBatch;
use App\Models\ImportedStudentRow;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Services\StudentAccountService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class StudentsImport implements ToCollection, WithHeadingRow
{
    private const PREVIEW_LIMIT = 10;

    private array $previewRows = [];
    private array $failedRows  = [];

    private int $validCount                 = 0;
    private int $createdCount                = 0;
    private int $duplicateCount              = 0;
    private int $alreadyExistsCount          = 0;
    private int $invalidNationalIdCount      = 0;
    private int $invalidRegistrationCount    = 0;
    private int $invalidDataCount            = 0;

    /** @var array<string,true> national IDs seen so far within this file */
    private array $seenNationalIds = [];
    /** @var array<string,true> registration numbers seen so far within this file */
    private array $seenRegistrationNumbers = [];

    private bool $dryRun;
    private ?int $importedBy;
    private ?ImportedStudentBatch $batch = null;

    public function __construct(bool $dryRun = false, ?int $importedBy = null)
    {
        $this->dryRun     = $dryRun;
        $this->importedBy = $importedBy;
    }

    public function collection(Collection $rows): void
    {
        if (! $this->dryRun) {
            $this->batch = ImportedStudentBatch::create([
                'imported_by'   => $this->importedBy ?? auth()->id(),
                'total_rows'    => 0,
                'success_count' => 0,
                'failed_count'  => 0,
            ]);
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2: 1-based index + heading row
            $this->processRow($row->toArray(), $rowNumber);
        }

        if ($this->batch) {
            $this->batch->update([
                'total_rows'    => $this->validCount + count($this->failedRows),
                'success_count' => $this->createdCount,
                'failed_count'  => count($this->failedRows),
            ]);
        }
    }

    private function processRow(array $row, int $rowNumber): void
    {
        $fullName = trim((string) ($row['full_name'] ?? ''));

        if (str_starts_with($fullName, '[EXAMPLE]')) {
            return;
        }

        $nationalId         = trim((string) ($row['national_id'] ?? ''));
        $registrationNumber = trim((string) ($row['registration_number'] ?? ''));

        [$status, $message] = $this->validate($row, $fullName, $nationalId, $registrationNumber);

        if (count($this->previewRows) < self::PREVIEW_LIMIT) {
            $this->previewRows[] = [
                'row_number'          => $rowNumber,
                'full_name'           => $fullName,
                'national_id'         => $nationalId,
                'registration_number' => $registrationNumber,
                'department_code'     => trim((string) ($row['department_code'] ?? '')),
                'specialization_name' => trim((string) ($row['specialization_name'] ?? '')),
                'semester'            => trim((string) ($row['semester'] ?? '')),
                'academic_year'       => trim((string) ($row['academic_year'] ?? '')),
                'valid'               => $status === null,
                'status'              => $status ?? 'valid',
                'error'               => $message,
            ];
        }

        if ($status !== null) {
            $this->fail($rowNumber, $status, $message, $fullName, $nationalId, $registrationNumber);

            return;
        }

        // Row is structurally valid — reserve its identifiers against
        // duplicates for the rest of this file (preview and real run alike).
        $this->seenNationalIds[$nationalId]                 = true;
        $this->seenRegistrationNumbers[$registrationNumber] = true;
        $this->validCount++;

        if ($this->dryRun) {
            return;
        }

        $this->createStudentAndAccount($row, $fullName, $nationalId, $registrationNumber, $rowNumber);
    }

    /**
     * @return array{0: ?string, 1: ?string} [status, message] — both null when the row is valid.
     */
    private function validate(array $row, string $fullName, string $nationalId, string $registrationNumber): array
    {
        if ($fullName === '') {
            return [ImportedStudentRow::STATUS_INVALID_DATA, 'الحقل المطلوب مفقود: full_name'];
        }

        foreach (['national_id', 'registration_number', 'department_code', 'specialization_name', 'semester', 'academic_year', 'date_of_birth'] as $field) {
            if (trim((string) ($row[$field] ?? '')) === '') {
                return [ImportedStudentRow::STATUS_INVALID_DATA, "الحقل المطلوب مفقود: {$field}"];
            }
        }

        if (! preg_match('/^\d{12}$/', $nationalId)) {
            return [ImportedStudentRow::STATUS_INVALID_NATIONAL_ID, 'الرقم الوطني يجب أن يتكون من 12 رقماً'];
        }

        $expectedLength = (int) SystemSetting::current()->registration_number_length;
        if (strlen($registrationNumber) !== $expectedLength) {
            return [ImportedStudentRow::STATUS_INVALID_REGISTRATION_NUMBER, "رقم القيد يجب أن يتكون من {$expectedLength} خانة"];
        }

        $department = Department::where('code', trim($row['department_code']))->first();
        if (! $department) {
            return [ImportedStudentRow::STATUS_INVALID_DATA, "القسم غير موجود: {$row['department_code']}"];
        }

        $specialization = Specialization::where('name', trim($row['specialization_name']))
            ->where('department_id', $department->id)
            ->first();
        if (! $specialization) {
            return [ImportedStudentRow::STATUS_INVALID_DATA, "التخصص غير موجود في هذا القسم: {$row['specialization_name']}"];
        }

        $semesterName = trim($row['semester']);
        if (! Semester::where('name', $semesterName)->exists()) {
            return [ImportedStudentRow::STATUS_INVALID_DATA, "الفصل الدراسي غير معروف: {$semesterName}"];
        }

        if ($this->parseDateOfBirth($row['date_of_birth']) === null) {
            return [ImportedStudentRow::STATUS_INVALID_DATA, 'تاريخ الميلاد غير صالح'];
        }

        if (isset($this->seenNationalIds[$nationalId]) || isset($this->seenRegistrationNumbers[$registrationNumber])) {
            return [ImportedStudentRow::STATUS_DUPLICATE_IN_FILE, 'الرقم الوطني أو رقم القيد مكرر داخل نفس الملف'];
        }

        if (Student::where('national_id', $nationalId)->orWhere('registration_number', $registrationNumber)->exists()) {
            return [ImportedStudentRow::STATUS_ALREADY_EXISTS, 'الطالب موجود مسبقاً في النظام'];
        }

        return [null, null];
    }

    private function parseDateOfBirth(mixed $value): ?Carbon
    {
        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            }

            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    private function createStudentAndAccount(array $row, string $fullName, string $nationalId, string $registrationNumber, int $rowNumber): void
    {
        try {
            $department     = Department::where('code', trim($row['department_code']))->first();
            $specialization = Specialization::where('name', trim($row['specialization_name']))
                ->where('department_id', $department->id)
                ->first();

            $student = app(StudentAccountService::class)->create([
                'full_name'           => $fullName,
                'national_id'         => $nationalId,
                'registration_number' => $registrationNumber,
                'department_id'       => $department->id,
                'specialization_id'   => $specialization->id,
                'semester'            => trim($row['semester']),
                'academic_year'       => trim($row['academic_year']),
                'date_of_birth'       => $this->parseDateOfBirth($row['date_of_birth']),
            ]);

            ImportedStudentRow::create([
                'batch_id'             => $this->batch->id,
                'row_number'           => $rowNumber,
                'full_name'            => $fullName,
                'national_id'          => $nationalId,
                'registration_number'  => $registrationNumber,
                'status'               => ImportedStudentRow::STATUS_SUCCESS,
                'student_id'           => $student->id,
            ]);

            $this->createdCount++;
        } catch (Throwable $e) {
            $this->fail($rowNumber, ImportedStudentRow::STATUS_INVALID_DATA, 'تعذّر إنشاء الحساب: ' . $e->getMessage(), $fullName, $nationalId, $registrationNumber);
        }
    }

    private function fail(int $rowNumber, string $status, string $reason, string $fullName, string $nationalId, string $registrationNumber): void
    {
        $this->failedRows[] = ['row_number' => $rowNumber, 'reason' => $reason, 'status' => $status];

        match ($status) {
            ImportedStudentRow::STATUS_DUPLICATE_IN_FILE           => $this->duplicateCount++,
            ImportedStudentRow::STATUS_ALREADY_EXISTS              => $this->alreadyExistsCount++,
            ImportedStudentRow::STATUS_INVALID_NATIONAL_ID         => $this->invalidNationalIdCount++,
            ImportedStudentRow::STATUS_INVALID_REGISTRATION_NUMBER => $this->invalidRegistrationCount++,
            default                                                => $this->invalidDataCount++,
        };

        if ($this->batch) {
            ImportedStudentRow::create([
                'batch_id'             => $this->batch->id,
                'row_number'           => $rowNumber,
                'full_name'            => $fullName ?: null,
                'national_id'          => $nationalId ?: null,
                'registration_number'  => $registrationNumber ?: null,
                'status'               => $status,
                'error_message'        => $reason,
            ]);
        }
    }

    public function getSummary(): array
    {
        return [
            'total_rows'                 => $this->validCount + count($this->failedRows),
            'valid_count'                => $this->validCount,
            'success_count'              => $this->dryRun ? $this->validCount : $this->createdCount,
            'duplicate_count'            => $this->duplicateCount,
            'already_exists_count'       => $this->alreadyExistsCount,
            'invalid_national_id_count'  => $this->invalidNationalIdCount,
            'invalid_registration_count' => $this->invalidRegistrationCount,
            'invalid_data_count'         => $this->invalidDataCount,
            'failed_count'               => count($this->failedRows),
            'failed_rows'                => $this->failedRows,
            'preview_rows'               => $this->previewRows,
        ];
    }
}
