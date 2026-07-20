<?php

namespace App\Providers;

use App\Models\ProjectFeedback;
use App\Policies\ProjectFeedbackPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        ProjectFeedback::class => ProjectFeedbackPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
