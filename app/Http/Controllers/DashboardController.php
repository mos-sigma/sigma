<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Project $project)
    {
        if ($project->exists === false && $this->userHasProjects()) {

            $projectId = $project = Auth::user()->projects->sortBy('id')->first()->id;

            return redirect()->route('dashboard', ['project' => $projectId]);
        }

        if ($project->exists === false) {
            return redirect()->route('project.create');
        }

        Gate::authorize('view-dashboard', $project);

        $trashedCluster = $project->clusters()->withTrashed()->first();

        $state = ($trashedCluster === null) ? null : $trashedCluster->state;
        $id = ($trashedCluster === null) ? null : $trashedCluster->id;

        return Inertia::render('dashboard', ['state' => $state, 'id' => $id]);
    }

    private function userHasProjects()
    {
        return Auth::user()->projects->isEmpty() === false;
    }
}
