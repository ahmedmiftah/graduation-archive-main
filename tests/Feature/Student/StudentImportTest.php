<?php

use App\Imports\StudentsImport;
use App\Models\Department;
use App\Models\ImportedStudentBatch;
use App\Models\ImportedStudentRow;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Notifications\StudentAccountCreated;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SemesterSeeder;
use Database\Seeders\SystemSettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RoleSeeder::class);
    $this->seed(SystemSettingSeeder::class); // registration_number_length = 9
    $this->seed(SemesterSeeder::class);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeStudentImportFile(array $rows): UploadedFile
{
    $headers = [
        'full_name', 'national_id', 'registration_number',
        'department_code', 'specialization_name', 'semester', 'academic_year', 'date_of_birth',
    ];

    $spreadsheet = new Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();
    $sheet->fromArray([$headers], null, 'A1');

    foreach ($rows as $i => $row) {
        $rowData = array_map(fn ($h) => $row[$h] ?? '', $headers);
        $sheet->fromArray([$rowData], null, 'A' . ($i + 2));
    }

    $path = sys_get_temp_dir() . '/student_import_test_' . uniqid() . '.xlsx';
    IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

    return new UploadedFile(
        $path,
        'students.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );
}

function makeStudentImportDeps(): array
{
    $dept = Department::factory()->create(['code' => 'CS']);
    $spec = Specialization::factory()->create(['name' => 'Software Engineering', 'department_id' => $dept->id]);
    Semester::firstOrCreate(['name' => 'خريف'], ['sort_order' => 1]);

    return compact('dept', 'spec');
}

function validStudentRow(array $deps, array $overrides = []): array
{
    return array_merge([
        'full_name'           => 'أحمد محمد علي',
        'national_id'         => '123456789012',
        'registration_number' => '202400123',
        'department_code'     => $deps['dept']->code,
        'specialization_name' => $deps['spec']->name,
        'semester'             => 'خريف',
        'academic_year'        => '2024',
        'date_of_birth'        => '2003-05-14',
    ], $overrides);
}

/** A real import always runs behind an authenticated dept_manager/super_admin — every "actual" import test needs an importer id. */
function studentImporterId(): int
{
    return userWithRole('super_admin')->id;
}

// ── 1. Access control ────────────────────────────────────────────────────────

test('super_admin can access student import page', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('students.import.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Students/Import'));
});

test('dept_manager can access student import page', function () {
    $this->actingAs(userWithRole('dept_manager'))
        ->get(route('students.import.index'))
        ->assertOk();
});

test('dept_staff cannot access student import page', function () {
    $this->actingAs(userWithRole('dept_staff'))
        ->get(route('students.import.index'))
        ->assertForbidden();
});

// ── 2. Template + file validation ───────────────────────────────────────────

test('student import template download returns valid xlsx file', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('students.import.template'))
        ->assertOk()
        ->assertDownload('students_import_template.xlsx');
});

test('student import rejects non-excel files', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs(userWithRole('super_admin'))
        ->from(route('students.import.index'))
        ->post(route('students.import.run'), ['file' => $file])
        ->assertSessionHasErrors('file');

    $this->assertDatabaseCount('students', 0);
});

// ── 3. Valid import creates student + account ───────────────────────────────

test('valid row creates student and a linked login account', function () {
    Notification::fake();

    $deps   = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: false, importedBy: userWithRole('super_admin')->id);
    Excel::import($import, makeStudentImportFile([validStudentRow($deps)]));

    $student = Student::where('registration_number', '202400123')->first();
    expect($student)->not->toBeNull()
        ->and($student->national_id)->toBe('123456789012')
        ->and($student->user)->not->toBeNull()
        ->and($student->user->email)->toBe('202400123@students.local')
        ->and($student->user->hasRole('student'))->toBeTrue()
        ->and($student->user->force_password_change)->toBeTrue()
        ->and($student->user->is_active)->toBeTrue();

    // Temporary password is the date of birth as DDMMYYYY.
    expect(\Illuminate\Support\Facades\Hash::check('14052003', $student->user->password))->toBeTrue();

    Notification::assertSentTo($student->user, StudentAccountCreated::class);

    expect($import->getSummary()['success_count'])->toBe(1);
});

test('actual import persists a batch and per-row audit trail', function () {
    $deps = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: false, importedBy: userWithRole('super_admin')->id);
    Excel::import($import, makeStudentImportFile([
        validStudentRow($deps),
        validStudentRow($deps, ['registration_number' => '202400999', 'national_id' => '999999999999', 'department_code' => 'MISSING']),
    ]));

    $this->assertDatabaseCount('imported_student_batches', 1);
    $batch = ImportedStudentBatch::first();
    expect($batch->success_count)->toBe(1)
        ->and($batch->failed_count)->toBe(1);

    expect(ImportedStudentRow::where('status', ImportedStudentRow::STATUS_SUCCESS)->count())->toBe(1)
        ->and(ImportedStudentRow::where('status', '!=', ImportedStudentRow::STATUS_SUCCESS)->count())->toBe(1);
});

// ── 4. Duplicate detection ──────────────────────────────────────────────────

test('duplicate national_id within the same file fails the second row only', function () {
    $deps   = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: false, importedBy: studentImporterId());
    Excel::import($import, makeStudentImportFile([
        validStudentRow($deps, ['registration_number' => '202400111']),
        validStudentRow($deps, ['registration_number' => '202400222']), // same national_id as row 1
    ]));

    $summary = $import->getSummary();
    expect($summary['success_count'])->toBe(1)
        ->and($summary['duplicate_count'])->toBe(1);

    $this->assertDatabaseCount('students', 1);
});

test('duplicate registration_number within the same file fails the second row only', function () {
    $deps   = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: false, importedBy: studentImporterId());
    Excel::import($import, makeStudentImportFile([
        validStudentRow($deps, ['national_id' => '111111111111']),
        validStudentRow($deps, ['national_id' => '222222222222']), // same registration_number as row 1
    ]));

    $summary = $import->getSummary();
    expect($summary['success_count'])->toBe(1)
        ->and($summary['duplicate_count'])->toBe(1);
});

test('a student already existing in the system is detected and not duplicated', function () {
    $deps = makeStudentImportDeps();
    Student::factory()->create([
        'national_id'         => '123456789012',
        'registration_number' => '999999999',
        'department_id'       => $deps['dept']->id,
        'specialization_id'   => $deps['spec']->id,
    ]);

    $import = new StudentsImport(dryRun: false, importedBy: studentImporterId());
    Excel::import($import, makeStudentImportFile([validStudentRow($deps)]));

    $summary = $import->getSummary();
    expect($summary['success_count'])->toBe(0)
        ->and($summary['already_exists_count'])->toBe(1);

    $this->assertDatabaseCount('students', 1); // no new row created
});

// ── 5. Field validation ─────────────────────────────────────────────────────

test('national_id must be exactly 12 digits', function () {
    $deps   = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: false, importedBy: studentImporterId());
    Excel::import($import, makeStudentImportFile([
        validStudentRow($deps, ['national_id' => '12345']),
    ]));

    $summary = $import->getSummary();
    expect($summary['invalid_national_id_count'])->toBe(1);
    $this->assertDatabaseCount('students', 0);
});

test('registration_number must match the configured system length', function () {
    $deps   = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: false, importedBy: studentImporterId());
    Excel::import($import, makeStudentImportFile([
        validStudentRow($deps, ['registration_number' => '123']), // setting default is 9
    ]));

    $summary = $import->getSummary();
    expect($summary['invalid_registration_count'])->toBe(1);
    $this->assertDatabaseCount('students', 0);
});

test('missing required field fails the row', function () {
    $deps   = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: false, importedBy: studentImporterId());
    Excel::import($import, makeStudentImportFile([
        validStudentRow($deps, ['full_name' => '']),
    ]));

    expect($import->getSummary()['invalid_data_count'])->toBe(1);
});

test('unknown department code fails the row', function () {
    $deps   = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: false, importedBy: studentImporterId());
    Excel::import($import, makeStudentImportFile([
        validStudentRow($deps, ['department_code' => 'NOPE']),
    ]));

    expect($import->getSummary()['invalid_data_count'])->toBe(1);
});

test('unknown semester fails the row', function () {
    $deps   = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: false, importedBy: studentImporterId());
    Excel::import($import, makeStudentImportFile([
        validStudentRow($deps, ['semester' => 'غير موجود']),
    ]));

    expect($import->getSummary()['invalid_data_count'])->toBe(1);
});

// ── 6. Preview vs actual ────────────────────────────────────────────────────

test('preview does not create any students, accounts, or audit rows', function () {
    $deps   = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: true);
    Excel::import($import, makeStudentImportFile([validStudentRow($deps)]));

    $summary = $import->getSummary();
    expect($summary['success_count'])->toBe(1)
        ->and($summary['preview_rows'])->toHaveCount(1)
        ->and($summary['preview_rows'][0]['valid'])->toBeTrue();

    $this->assertDatabaseCount('students', 0);
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('imported_student_batches', 0);
});

// ── 7. Summary counts ────────────────────────────────────────────────────────

test('import summary reports correct mixed counts', function () {
    $deps   = makeStudentImportDeps();
    $import = new StudentsImport(dryRun: false, importedBy: studentImporterId());
    Excel::import($import, makeStudentImportFile([
        validStudentRow($deps, ['registration_number' => '202400001', 'national_id' => '111111111111']),
        validStudentRow($deps, ['registration_number' => '202400002', 'national_id' => '222222222222']),
        validStudentRow($deps, ['national_id' => '333', 'registration_number' => '202400003']), // invalid national id
    ]));

    $summary = $import->getSummary();
    expect($summary['total_rows'])->toBe(3)
        ->and($summary['success_count'])->toBe(2)
        ->and($summary['failed_count'])->toBe(1);

    $this->assertDatabaseCount('students', 2);
});
