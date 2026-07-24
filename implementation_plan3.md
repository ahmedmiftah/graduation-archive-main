# Add Semester Field to Projects

This plan outlines the steps required to add a "semester" (الفصل الدراسي) field to the projects table, validation requests, controllers, and create/edit forms.

## Proposed Changes

### Database Migration

#### [NEW] [2026_07_24_000000_add_semester_to_projects_table.php](file:///c:/Users/hp/Desktop/graduation-archive-main/database/migrations/2026_07_24_000000_add_semester_to_projects_table.php)
Create a migration to add the `semester` column (nullable string, after `academic_year`) to the `projects` table.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('semester')->nullable()->after('academic_year'); // ربيع, خريف
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('semester');
        });
    }
};
```

---

### Backend Models & Requests

#### [MODIFY] [Project.php](file:///c:/Users/hp/Desktop/graduation-archive-main/app/Models/Project.php)
Add `semester` to the `$fillable` array.

#### [MODIFY] [StoreProjectRequest.php](file:///c:/Users/hp/Desktop/graduation-archive-main/app/Http/Requests/StoreProjectRequest.php)
Add validation rules for `semester`:
```php
'semester' => ['required', 'string', 'in:ربيع,خريف'],
```

#### [MODIFY] [UpdateProjectRequest.php](file:///c:/Users/hp/Desktop/graduation-archive-main/app/Http/Requests/UpdateProjectRequest.php)
Add validation rules for `semester`:
```php
'semester' => ['required', 'string', 'in:ربيع,خريف'],
```

---

### Controller

#### [MODIFY] [ProjectController.php](file:///c:/Users/hp/Desktop/graduation-archive-main/app/Http/Controllers/ProjectController.php)
Update the `store` and `update` methods to include `semester` in the `Project::create` and `$project->update` array attributes.

---

### Frontend Views

#### [MODIFY] [Create.vue](file:///c:/Users/hp/Desktop/graduation-archive-main/resources/js/Pages/Projects/Create.vue)
- Add `semester: ''` to `form`.
- Add a dropdown select field in the form template for "الفصل الدراسي" with options:
  - "ربيع" (Spring)
  - "خريف" (Autumn)

#### [MODIFY] [Edit.vue](file:///c:/Users/hp/Desktop/graduation-archive-main/resources/js/Pages/Projects/Edit.vue)
- Add `semester: props.project.semester` to `form`.
- Add a dropdown select field in the form template for "الفصل الدراسي" with options:
  - "ربيع" (Spring)
  - "خريف" (Autumn)

#### [MODIFY] [Show.vue (Projects)](file:///c:/Users/hp/Desktop/graduation-archive-main/resources/js/pages/Projects/Show.vue)
- Display the semester on the project details page.

#### [MODIFY] [Show.vue (Public)](file:///c:/Users/hp/Desktop/graduation-archive-main/resources/js/Pages/Public/Show.vue)
- Display the semester on the public project details page.

## Verification Plan

### Automated Verification
- Run migrations: `php artisan migrate`
- Verify database update: Check `projects` table structure.

### Manual Verification
- Go to "إضافة مشروع" (Add Project), verify the Semester dropdown exists and select an option. Save and verify it preserves the choice.
- Edit the project, change the semester, save, and verify it updates.
- View the project details page (admin and public) to verify the selected semester is displayed.
