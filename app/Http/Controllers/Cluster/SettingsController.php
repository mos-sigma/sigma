<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cluster;

use App\Models\Project;
use Inertia\Inertia;

class SettingsController extends \App\Http\Controllers\Controller
{
    /**
     * Show project settings page
     */
    public function index(Project $project)
    {
        $cluster = $project->getAttribute('clusters')->first();
        $clusterId = null;

        if ($cluster !== null) {
            $clusterId = $cluster->getAttribute('id');
        }

        return Inertia::render('settings/index', ['clusterId' => $clusterId]);
    }
}