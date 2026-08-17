<?php

use App\Models\Department;
use App\Models\Project;
use App\Models\Specialization;
use Database\Seeders\ProjectStatusSeeder;

beforeEach(function () {
    $this->seed(ProjectStatusSeeder::class);
});

test('the /student portal page is publicly accessible without login', function () {
    $dept = Department::factory()->create();
    $spec = Specialization::factory()->create(['department_id' => $dept->id]);
    Project::factory()->create([
        'department_id'     => $dept->id,
        'specialization_id' => $spec->id,
        'current_status_id' => 1,
        'is_deleted'        => false,
    ]);

    $this->get(route('student.portal'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/Browse')
            ->where('routeName', 'student.portal')
            ->where('loginLabel', 'دخول طالب')
            ->has('projects.data', 1)
        );
});

test('the original /browse page is completely unaffected', function () {
    $this->get(route('public.browse'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/Browse')
            ->where('routeName', 'public.browse')
            ->where('loginLabel', 'تسجيل الدخول')
        );
});
