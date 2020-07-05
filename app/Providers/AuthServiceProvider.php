<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('view-dashboard', fn ($user, $project) => $project->user_id === $user->id);

        Gate::define('create-cluster', function ($user, $project) {

            $projectBelongsToUser = $project->user->id === $user->id;
            $projectHasNotCluster = $project->clusters()->withTrashed()->get()->isEmpty();

            return $projectBelongsToUser && $projectHasNotCluster;
        });
    }
}
