<?php

namespace App\Providers;

use App\Repositories\Eloquent\OrganizationRepository;
use App\Repositories\Eloquent\ReviewRepository;
use App\Repositories\OrganizationRepositoryInterface;
use App\Repositories\ReviewRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrganizationRepositoryInterface::class, OrganizationRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, ReviewRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
