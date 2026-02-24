<?php

namespace App\Listeners;

use App\Services\OnboardingService;
use Illuminate\Auth\Events\Registered;

class SetupNewUser
{
    public function __construct(protected OnboardingService $onboardingService)
    {
    }

    public function handle(Registered $event): void
    {
        $this->onboardingService->seedDefaultLifeAreas($event->user);
    }
}
