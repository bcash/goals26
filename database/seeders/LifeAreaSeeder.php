<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LifeAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            [
                'user_id' => 1,
                'name' => 'Creative',
                'icon' => '🎨',
                'color_hex' => '#9333EA',
                'description' => 'Writing, music, TV production, and all creative output.',
                'sort_order' => 1,
            ],
            [
                'user_id' => 1,
                'name' => 'Business',
                'icon' => '💼',
                'color_hex' => '#3B82F6',
                'description' => 'Client work, team management, revenue, and growth.',
                'sort_order' => 2,
            ],
            [
                'user_id' => 1,
                'name' => 'Health',
                'icon' => '💚',
                'color_hex' => '#22C55E',
                'description' => 'Physical wellness, mental health, energy, sleep, and nutrition.',
                'sort_order' => 3,
            ],
            [
                'user_id' => 1,
                'name' => 'Family',
                'icon' => '👨‍👩‍👧',
                'color_hex' => '#EC4899',
                'description' => 'Relationships, presence, shared experiences, and legacy.',
                'sort_order' => 4,
            ],
            [
                'user_id' => 1,
                'name' => 'Growth',
                'icon' => '📚',
                'color_hex' => '#F59E0B',
                'description' => 'Learning, skills, reading, courses, and spiritual development.',
                'sort_order' => 5,
            ],
            [
                'user_id' => 1,
                'name' => 'Finance',
                'icon' => '💰',
                'color_hex' => '#10B981',
                'description' => 'Income, expenses, savings, investments, and financial goals.',
                'sort_order' => 6,
            ],
        ];

        foreach ($areas as $area) {
            DB::table('life_areas')->updateOrInsert(
                ['user_id' => $area['user_id'], 'name' => $area['name']],
                array_merge($area, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
