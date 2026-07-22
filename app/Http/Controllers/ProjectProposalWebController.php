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
        $departmentId = $user->department_id;

        $specializations = $departmentId 
            ? Specialization::where('department_id', $departmentId)->get(['id', 'name']) 
            : [];
            
        $supervisors = $departmentId
            ? User::role('supervisor')->where('department_id', $departmentId)->get(['id', 'name'])
            : [];

        return Inertia::render('Proposals/Create', [
            'specializations' => $specializations,
            'supervisors' => $supervisors,
        ]);
    }

    /**
     * Display the show page.
     */
    public function show(ProjectProposal $proposal)
    {
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
        $departmentId = $user->department_id ?? $proposal->department_id;

        $specializations = $departmentId 
            ? Specialization::where('department_id', $departmentId)->get(['id', 'name']) 
            : [];
            
        $supervisors = $departmentId
            ? User::role('supervisor')->where('department_id', $departmentId)->get(['id', 'name'])
            : [];

        return Inertia::render('Proposals/Edit', [
            'proposalId' => $proposal->id,
            'specializations' => $specializations,
            'supervisors' => $supervisors,
        ]);
    }
}
?>
