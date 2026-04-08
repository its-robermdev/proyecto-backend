<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Submission;
use App\Models\User;
use App\Policies\EventPolicy;
use App\Policies\SubmissionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registro de bindings globales (actualmente no se requieren personalizados).
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Vincula modelos con policies para autorización centralizada por Gate.
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
