<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ProjectProposal;
use App\Models\FacultyMember;
use App\Models\User;
use App\Models\Department;
use App\Models\Specialization;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectProposalTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_belongs_to_a_department()
    {
        $department = Department::factory()->create();
        $proposal = ProjectProposal::factory()->create(['department_id' => $department->id]);

        $this->assertInstanceOf(Department::class, $proposal->department);
        $this->assertEquals($department->id, $proposal->department->id);
    }

    public function test_it_belongs_to_a_specialization()
    {
        $specialization = Specialization::factory()->create();
        $proposal = ProjectProposal::factory()->create(['specialization_id' => $specialization->id]);

        $this->assertInstanceOf(Specialization::class, $proposal->specialization);
        $this->assertEquals($specialization->id, $proposal->specialization->id);
    }

    public function test_it_has_students()
    {
        $proposal = ProjectProposal::factory()->create();
        $proposal->students()->create(['full_name' => 'أحمد علي', 'registration_number' => '2026001']);
        $proposal->students()->create(['full_name' => 'سارة محمد', 'registration_number' => '2026002']);

        $this->assertCount(2, $proposal->fresh()->students);
        $this->assertEquals('2026001', $proposal->students->first()->registration_number);
    }

    public function test_it_has_a_creator_and_optional_supervisor()
    {
        $creator = User::factory()->create();
        $supervisor = FacultyMember::factory()->create();

        $proposal = ProjectProposal::factory()->create([
            'created_by' => $creator->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $this->assertInstanceOf(User::class, $proposal->creator);
        $this->assertEquals($creator->id, $proposal->creator->id);

        $this->assertInstanceOf(FacultyMember::class, $proposal->supervisor);
        $this->assertEquals($supervisor->id, $proposal->supervisor->id);
    }
}
?>
