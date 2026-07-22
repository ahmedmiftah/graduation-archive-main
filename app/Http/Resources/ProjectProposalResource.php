<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectProposalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'department' => $this->department ? $this->department->only(['id', 'name']) : null,
            'specialization' => $this->specialization ? $this->specialization->only(['id', 'name']) : null,
            'academic_year' => $this->academic_year,
            'semester' => $this->semester,
            'submission_date' => $this->submission_date ? $this->submission_date->toDateString() : null,
            'students' => $this->students->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'registration_number' => $s->registration_number,
            ]),
            'supervisor' => $this->supervisor ? $this->supervisor->only(['id', 'name', 'email']) : null,
            'status' => $this->status,
            'committee_decision' => $this->committee_decision,
            'committee_notes' => $this->committee_notes,
            'pdf_url' => $this->pdf_file ? url('storage/' . $this->pdf_file) : null,
            'created_by' => $this->creator ? $this->creator->only(['id', 'name']) : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
?>
