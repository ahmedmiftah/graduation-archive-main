<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ProjectProposal;
use App\Models\User;
use App\Models\Specialization;
use Illuminate\Support\Facades\Auth;

class ProjectProposalWebController extends Controller
{
    /**
     * Display the index page.
     */
    public function index()
    {
        return Inertia::render('Proposals/Index');
    }

    /**
     * Display the create page.
     */
    public function create()
    {
        $user = Auth::user();
        $departmentId = $user->hasRole('super_admin') ? null : $user->department_id;

        $departmentsQuery = \App\Models\Department::orderBy('name');
        if ($departmentId) {
            $departmentsQuery->where('id', $departmentId);
        }

        $specializationsQuery = Specialization::orderBy('name');
        if ($departmentId) {
            $specializationsQuery->where('department_id', $departmentId);
        }

        $supervisorsQuery = User::role('supervisor')->orderBy('name');
        if ($departmentId) {
            $supervisorsQuery->where('department_id', $departmentId);
        }

        return Inertia::render('Proposals/Create', [
            'departments' => $departmentsQuery->get(['id', 'name']),
            'specializations' => $specializationsQuery->get(['id', 'name', 'department_id']),
            'supervisors' => $supervisorsQuery->get(['id', 'name', 'department_id']),
        ]);
    }

    /**
     * Display the show page.
     */
    public function show(ProjectProposal $proposal)
    {
        if ($user = Auth::user()) {
            $user->unreadNotifications()
                ->where('data->proposal_id', $proposal->id)
                ->update(['read_at' => now()]);
        }

        return Inertia::render('Proposals/Show', [
            'proposalId' => $proposal->id
        ]);
    }

    /**
     * Display the edit page.
     */
    public function edit(ProjectProposal $proposal)
    {
        $user = Auth::user();
        $departmentId = $user->hasRole('super_admin') ? null : ($user->department_id ?? $proposal->department_id);

        $departmentsQuery = \App\Models\Department::orderBy('name');
        if ($departmentId) {
            $departmentsQuery->where('id', $departmentId);
        }

        $specializationsQuery = Specialization::orderBy('name');
        if ($departmentId) {
            $specializationsQuery->where('department_id', $departmentId);
        }

        $supervisorsQuery = User::role('supervisor')->orderBy('name');
        if ($departmentId) {
            $supervisorsQuery->where('department_id', $departmentId);
        }

        return Inertia::render('Proposals/Edit', [
            'proposalId' => $proposal->id,
            'departments' => $departmentsQuery->get(['id', 'name']),
            'specializations' => $specializationsQuery->get(['id', 'name', 'department_id']),
            'supervisors' => $supervisorsQuery->get(['id', 'name', 'department_id']),
        ]);
    }
}
?>
