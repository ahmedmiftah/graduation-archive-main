<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ProjectProposal;
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
        $student1 = User::factory()->create();
        $student2 = User::factory()->create();

        $proposal->students()->attach([$student1->id, $student2->id]);

        $this->assertCount(2, $proposal->students);
        $this->assertTrue($proposal->students->contains($student1));
        $this->assertTrue($proposal->students->contains($student2));
    }

    public function test_it_has_a_creator_and_optional_supervisor()
    {
        $creator = User::factory()->create();
        $supervisor = User::factory()->create();

        $proposal = ProjectProposal::factory()->create([
            'created_by' => $creator->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $this->assertInstanceOf(User::class, $proposal->creator);
        $this->assertEquals($creator->id, $proposal->creator->id);

        $this->assertInstanceOf(User::class, $proposal->supervisor);
        $this->assertEquals($supervisor->id, $proposal->supervisor->id);
    }
}
?>
