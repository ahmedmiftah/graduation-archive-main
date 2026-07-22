<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\ProjectProposal;
use App\Models\Department;
use App\Models\Specialization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class ProjectProposalApiTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $deptHeadUser;
    protected $department;
    protected $specialization;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->department = Department::factory()->create();
        $this->specialization = Specialization::factory()->create();

        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'dept-head']);
        Role::create(['name' => 'dept-manager']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super-admin');

        $this->deptHeadUser = User::factory()->create();
        $this->deptHeadUser->assignRole('dept-head');
    }

    public function test_can_list_proposals()
    {
        ProjectProposal::factory()->count(3)->create([
            'department_id' => $this->department->id,
            'specialization_id' => $this->specialization->id,
            'created_by' => $this->adminUser->id
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('api.proposals.index'));

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'title', 'status']]]);
    }

    public function test_can_create_proposal_with_pdf()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $payload = [
            'title' => 'Test Proposal',
            'description' => 'Test Description',
            'department_id' => $this->department->id,
            'specialization_id' => $this->specialization->id,
            'academic_year' => '2023/2024',
            'status' => 'new',
            'pdf_file' => $file
        ];

        $response = $this->actingAs($this->adminUser)->postJson(route('api.proposals.store'), $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment(['title' => 'Test Proposal']);

        $proposal = ProjectProposal::first();
        Storage::disk('public')->assertExists($proposal->pdf_file);
    }

    public function test_can_change_proposal_status()
    {
        $proposal = ProjectProposal::factory()->create([
            'department_id' => $this->department->id,
            'specialization_id' => $this->specialization->id,
            'created_by' => $this->adminUser->id,
            'status' => 'new'
        ]);

        $payload = [
            'status' => 'approved',
            'committee_decision' => 'accepted',
            'committee_notes' => 'Looks good'
        ];

        $response = $this->actingAs($this->deptHeadUser)->postJson(route('api.proposals.change-status', $proposal->id), $payload);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('project_proposals', [
            'id' => $proposal->id,
            'status' => 'approved',
            'committee_decision' => 'accepted'
        ]);
    }
}
?>
