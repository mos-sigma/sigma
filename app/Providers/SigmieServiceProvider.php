<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\BaseSigmieService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Sigmie\Search\SigmieClient;

class SigmieServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(BaseSigmieService::class, function ($app) {

            $cluster = Route::getCurrentRoute()->parameter('project')->clusters()->first();

            return new BaseSigmieService($cluster);

            // return SigmieClient::createFromBasicAuth($username, $password, $url);
        });
    }
}
