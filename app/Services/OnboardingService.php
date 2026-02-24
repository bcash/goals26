<?php

namespace App\Services;

use App\Models\LifeArea;
use App\Models\User;

class OnboardingService
{
    /**
     * Seed the default 6 life areas for a newly registered user.
     */
    public function seedDefaultLifeAreas(User $user): void
    {
        $defaults = [
            [
                'name' => 'Creative',
                'icon' => '🎨',
                'color_hex' => '#9333EA',
                'sort_order' => 1,
                'description' => 'Writing, music, TV production, and all creative output.',
            ],
            [
                'name' => 'Business',
                'icon' => '💼',
                'color_hex' => '#3B82F6',
                'sort_order' => 2,
                'description' => 'Client work, team management, revenue, and growth.',
            ],
            [
                'name' => 'Health',
                'icon' => '💚',
                'color_hex' => '#22C55E',
                'sort_order' => 3,
                'description' => 'Physical wellness, mental health, energy, sleep, and nutrition.',
            ],
            [
                'name' => 'Family',
                'icon' => '👨‍👩‍👧',
                'color_hex' => '#EC4899',
                'sort_order' => 4,
                'description' => 'Relationships, presence, shared experiences, and legacy.',
            ],
            [
                'name' => 'Growth',
                'icon' => '📚',
                'color_hex' => '#F59E0B',
                'sort_order' => 5,
                'description' => 'Learning, skills, reading, courses, and spiritual development.',
            ],
            [
                'name' => 'Finance',
                'icon' => '💰',
                'color_hex' => '#10B981',
                'sort_order' => 6,
                'description' => 'Income, expenses, savings, investments, and financial goals.',
            ],
        ];

        foreach ($defaults as $area) {
            LifeArea::create(array_merge($area, ['user_id' => $user->id]));
        }
    }

    /**
     * Mark a user's onboarding as complete.
     */
    public function completeOnboarding(User $user): void
    {
        $user->update(['onboarding_complete' => true]);
    }
}
