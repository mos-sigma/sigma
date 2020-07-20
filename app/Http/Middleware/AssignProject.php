<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Repositories\ProjectRepository;
use Closure;
use Illuminate\Support\Facades\Auth;

class AssignProject
{
    /**
     * Assign project to the route if it was called without one
     */
    public function handle($request, Closure $next)
    {
        $project = $request->route('project');

        if ($project instanceof Project) {
            return $next($request);
        }

        // Get the first project id
        $project = Auth::user()->getAttribute('projects')->first();

        if ($project instanceof Project) {

            $projectId = $project->getAttribute('id');

            $routeName = $request->route()->getName();

            return redirect()->route($routeName, ['project' => $projectId]);
        }

        return redirect()->route('project.create');
    }
}