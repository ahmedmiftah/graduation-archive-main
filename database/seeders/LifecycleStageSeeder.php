<?php

namespace Database\Seeders;

use App\Models\LifecycleStage;
use Illuminate\Database\Seeder;

class LifecycleStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['key' => 'supervisor_selection',  'name_ar' => 'اختيار المشرف',             'sort_order' => 1,  'requires_student_action' => true],
            ['key' => 'proposal_submission',   'name_ar' => 'تقديم المقترح',              'sort_order' => 2,  'requires_student_action' => true],
            ['key' => 'supervisor_review',     'name_ar' => 'مراجعة المشرف',              'sort_order' => 3,  'requires_student_action' => false],
            ['key' => 'department_review',     'name_ar' => 'مراجعة القسم',               'sort_order' => 4,  'requires_student_action' => false],
            ['key' => 'proposal_approval',     'name_ar' => 'اعتماد المقترح',             'sort_order' => 5,  'requires_student_action' => false],
            ['key' => 'project_start',         'name_ar' => 'بدء المشروع',                'sort_order' => 6,  'requires_student_action' => false],
            ['key' => 'documentation_upload',  'name_ar' => 'رفع التوثيق',                'sort_order' => 7,  'requires_student_action' => true],
            ['key' => 'supervisor_approval',   'name_ar' => 'موافقة المشرف',              'sort_order' => 8,  'requires_student_action' => false],
            ['key' => 'defense_readiness',     'name_ar' => 'الجاهزية للمناقشة',          'sort_order' => 9,  'requires_student_action' => false],
            ['key' => 'practical_defense',     'name_ar' => 'المناقشة العملية',           'sort_order' => 10, 'requires_student_action' => false],
            ['key' => 'field_training',        'name_ar' => 'التدريب الميداني',           'sort_order' => 11, 'requires_student_action' => false],
            ['key' => 'final_defense',         'name_ar' => 'المناقشة النهائية',          'sort_order' => 12, 'requires_student_action' => false],
            ['key' => 'revisions',             'name_ar' => 'التعديلات',                  'sort_order' => 13, 'requires_student_action' => true],
            ['key' => 'final_version',         'name_ar' => 'النسخة النهائية',            'sort_order' => 14, 'requires_student_action' => true],
            ['key' => 'project_completion',    'name_ar' => 'إنجاز المشروع',              'sort_order' => 15, 'requires_student_action' => false],
            ['key' => 'clearance',             'name_ar' => 'إخلاء الطرف',                'sort_order' => 16, 'requires_student_action' => false],
            ['key' => 'graduation_completion', 'name_ar' => 'اكتمال إجراءات التخرج',      'sort_order' => 17, 'requires_student_action' => false],
        ];

        foreach ($stages as $stage) {
            LifecycleStage::updateOrCreate(['key' => $stage['key']], $stage);
        }
    }
}
