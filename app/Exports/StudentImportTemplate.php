<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentImportTemplate implements FromArray, WithHeadings, WithStyles
{
    use Exportable;

    public function headings(): array
    {
        return [
            'full_name',
            'national_id',
            'registration_number',
            'department_code',
            'specialization_name',
            'semester',
            'academic_year',
            'date_of_birth',
        ];
    }

    public function array(): array
    {
        return [
            [
                '[EXAMPLE] أحمد محمد علي',
                '123456789012',
                '202400123',
                'CS',
                'هندسة البرمجيات',
                'خريف',
                '2024',
                '2003-05-14',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E40AF'],
                ],
            ],
            2 => [
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF9C3'],
                ],
                'font' => ['italic' => true, 'color' => ['rgb' => '92400E']],
            ],
        ];
    }
}
