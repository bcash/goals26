<?php

namespace Database\Seeders;

use App\Models\AgendaItem;
use App\Models\AiInteraction;
use App\Models\DailyPlan;
use App\Models\DeferralReview;
use App\Models\DeferredItem;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\JournalEntry;
use App\Models\LifeArea;
use App\Models\MeetingAgenda;
use App\Models\MeetingDoneItem;
use App\Models\MeetingNote;
use App\Models\MeetingResourceSignal;
use App\Models\MeetingScopeItem;
use App\Models\Milestone;
use App\Models\OpportunityPipeline;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\Task;
use App\Models\TaskQualityGate;
use App\Models\TimeBlock;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WeeklyReview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── User ──────────────────────────────────────────────────────
        $user = User::create([
            'name' => 'Brian Cash',
            'email' => 'admin@solasrun.com',
            'password' => Hash::make('password'),
            'timezone' => 'America/New_York',
            'subscription_status' => 'active',
            'onboarding_complete' => true,
        ]);

        $userId = $user->id;

        // ── Life Areas ───────────────────────────────────────────────
        $areas = [];
        $areaData = [
            ['Creative', '#9333EA', 'paint-brush', 'Creative projects and artistic expression'],
            ['Business', '#3B82F6', 'briefcase', 'Professional growth and business ventures'],
            ['Health', '#22C55E', 'heart', 'Physical and mental wellness'],
            ['Family', '#EC4899', 'home', 'Relationships and family connections'],
            ['Growth', '#F59E0B', 'academic-cap', 'Learning and personal development'],
            ['Finance', '#10B981', 'currency-dollar', 'Financial health and planning'],
        ];

        foreach ($areaData as $i => [$name, $color, $icon, $desc]) {
            $areas[$name] = LifeArea::create([
                'user_id' => $userId,
                'name' => $name,
                'color_hex' => $color,
                'icon' => $icon,
                'description' => $desc,
                'sort_order' => $i + 1,
            ]);
        }

        // ── Goals ─────────────────────────────────────────────────────
        $goals = [];
        $goalData = [
            ['Launch freelance design business', 'Creative', '1-year', 'active', 45, 'Build a sustainable freelance design practice serving 3-5 ongoing clients'],
            ['Publish a short story collection', 'Creative', '3-year', 'active', 20, 'Write and publish a collection of 12 short stories'],
            ['Run a half marathon', 'Health', '1-year', 'active', 60, 'Train for and complete a half marathon under 2 hours'],
            ['Meditate 365 days', 'Health', '1-year', 'active', 75, 'Build an unbroken daily meditation practice'],
            ['Read 24 books this year', 'Growth', '1-year', 'active', 50, 'Read broadly across fiction, non-fiction, and professional development'],
            ['Complete AWS Solutions Architect cert', 'Growth', '90-day', 'active', 30, 'Pass the AWS SA Associate exam'],
            ['Save 6-month emergency fund', 'Finance', '1-year', 'active', 40, 'Build emergency savings to cover 6 months of expenses'],
            ['Plan family reunion for summer', 'Family', '90-day', 'achieved', 100, 'Organize and host annual family reunion'],
            ['Build SaaS product MVP', 'Business', '1-year', 'active', 35, 'Design, build, and launch an MVP for project management tool'],
            ['Grow newsletter to 1000 subscribers', 'Business', '1-year', 'paused', 15, 'Build weekly newsletter about design and technology'],
        ];

        foreach ($goalData as [$title, $area, $horizon, $status, $progress, $why]) {
            $goals[] = Goal::create([
                'user_id' => $userId,
                'life_area_id' => $areas[$area]->id,
                'title' => $title,
                'description' => "Goal: {$title}",
                'why' => $why,
                'horizon' => $horizon,
                'status' => $status,
                'progress_percent' => $progress,
                'target_date' => now()->addMonths(rand(2, 12)),
            ]);
        }

        // ── Milestones ────────────────────────────────────────────────
        $milestoneData = [
            [0, 'Set up portfolio website', now()->subDays(10), now()->subDays(5)],
            [0, 'Land first client', now()->addDays(30), null],
            [0, 'Reach $5k monthly revenue', now()->addDays(90), null],
            [2, 'Complete Couch to 10K program', now()->subDays(5), now()->subDays(2)],
            [2, 'Run 10 miles without stopping', now()->addDays(30), null],
            [2, 'Register for race', now()->addDays(60), null],
            [4, 'Finish 12 books', now()->addDays(60), null],
            [4, 'Finish 24 books', now()->addDays(180), null],
            [5, 'Complete all practice exams', now()->addDays(20), null],
            [5, 'Schedule exam date', now()->addDays(25), null],
            [8, 'Complete wireframes', now()->subDays(20), now()->subDays(18)],
            [8, 'Build core features', now()->addDays(45), null],
            [8, 'Beta launch', now()->addDays(90), null],
        ];

        foreach ($milestoneData as $i => [$goalIdx, $title, $dueDate, $completedAt]) {
            Milestone::create([
                'goal_id' => $goals[$goalIdx]->id,
                'title' => $title,
                'due_date' => $dueDate,
                'status' => $completedAt ? 'complete' : 'pending',
                'sort_order' => $i,
            ]);
        }

        // ── Projects ──────────────────────────────────────────────────
        $projects = [];
        $projectData = [
            ['Acme Corp Website Redesign', 'Business', 'active', 'Acme Corp', '#3B82F6'],
            ['Riverside Studio Branding', 'Creative', 'active', 'Riverside Studio', '#9333EA'],
            ['Personal Portfolio', 'Creative', 'active', null, '#F59E0B'],
            ['SaaS MVP Development', 'Business', 'active', null, '#10B981'],
            ['Home Renovation Planning', 'Family', 'active', null, '#EC4899'],
            ['Investment Research', 'Finance', 'complete', null, '#22C55E'],
        ];

        foreach ($projectData as [$name, $area, $status, $client, $color]) {
            $projects[] = Project::create([
                'user_id' => $userId,
                'life_area_id' => $areas[$area]->id,
                'goal_id' => $goals[0]->id,
                'name' => $name,
                'description' => "Project: {$name}",
                'status' => $status,
                'client_name' => $client,
                'color_hex' => $color,
                'due_date' => now()->addDays(rand(14, 120)),
            ]);
        }

        // ── Tasks ─────────────────────────────────────────────────────
        $taskTitles = [
            // Root tasks for Acme project
            ['Design landing page mockup', 0, 'high', 'done', true, 0],
            ['Implement responsive navigation', 0, 'high', 'in-progress', true, 0],
            ['Write copy for about page', 0, 'medium', 'todo', false, 0],
            ['Set up CI/CD pipeline', 0, 'medium', 'done', false, 0],
            ['Client review meeting prep', 0, 'high', 'todo', true, 0],
            // Riverside project
            ['Create mood board', 1, 'high', 'done', false, 0],
            ['Design logo options', 1, 'high', 'in-progress', false, 0],
            ['Typography selection', 1, 'medium', 'todo', false, 0],
            ['Brand guidelines document', 1, 'medium', 'todo', false, 0],
            // Personal portfolio
            ['Select portfolio template', 2, 'medium', 'done', false, 0],
            ['Write case studies', 2, 'high', 'in-progress', true, 0],
            ['Add project screenshots', 2, 'medium', 'todo', false, 0],
            // SaaS MVP
            ['Design database schema', 3, 'critical', 'done', false, 0],
            ['Build user authentication', 3, 'critical', 'in-progress', false, 0],
            ['Create dashboard wireframes', 3, 'high', 'done', false, 0],
            ['Implement API endpoints', 3, 'high', 'todo', false, 0],
            ['Write unit tests', 3, 'medium', 'todo', false, 0],
            // Home reno
            ['Get contractor quotes', 4, 'high', 'done', false, 0],
            ['Choose paint colors', 4, 'medium', 'in-progress', false, 0],
            ['Order kitchen cabinets', 4, 'high', 'todo', false, 0],
            // Standalone tasks
            ['Review client contract for Acme', 0, 'critical', 'todo', true, 0],
            ['Write Q4 proposal', 0, 'high', 'todo', true, 0],
            ['Update LinkedIn profile', 2, 'low', 'todo', false, 0],
            ['Research SEO best practices', 3, 'medium', 'deferred', false, 0],
            ['Set up email marketing', 3, 'medium', 'deferred', false, 0],
        ];

        $tasks = [];
        foreach ($taskTitles as [$title, $projIdx, $priority, $status, $isDaily, $depth]) {
            $tasks[] = Task::create([
                'user_id' => $userId,
                'project_id' => $projects[$projIdx]->id,
                'goal_id' => $goals[0]->id,
                'title' => $title,
                'status' => $status,
                'priority' => $priority,
                'is_daily_action' => $isDaily,
                'depth' => $depth,
                'is_leaf' => true,
                'sort_order' => count($tasks),
                'due_date' => now()->addDays(rand(-5, 30)),
                'time_estimate_minutes' => [30, 60, 90, 120, 180][rand(0, 4)],
                'decomposition_status' => $status === 'done' ? 'complete' : 'ready',
            ]);
        }

        // Add some child tasks for tree structure
        $parentTask = $tasks[1]; // "Implement responsive navigation"
        $childTitles = ['Build mobile hamburger menu', 'Add dropdown animations', 'Test across browsers'];
        foreach ($childTitles as $i => $title) {
            $child = Task::create([
                'user_id' => $userId,
                'project_id' => $parentTask->project_id,
                'goal_id' => $parentTask->goal_id,
                'parent_id' => $parentTask->id,
                'title' => $title,
                'status' => $i === 0 ? 'done' : 'todo',
                'priority' => 'medium',
                'depth' => 1,
                'path' => $parentTask->id.'/',
                'is_leaf' => true,
                'sort_order' => $i,
                'due_date' => now()->addDays(rand(1, 14)),
                'time_estimate_minutes' => rand(30, 120),
            ]);
            $tasks[] = $child;
        }
        $parentTask->update(['is_leaf' => false]);

        // Add sub-children
        $subParent = $tasks[count($tasks) - 3]; // "Build mobile hamburger menu"
        foreach (['Style hamburger icon', 'Add slide animation'] as $i => $title) {
            Task::create([
                'user_id' => $userId,
                'project_id' => $subParent->project_id,
                'parent_id' => $subParent->id,
                'title' => $title,
                'status' => 'done',
                'priority' => 'medium',
                'depth' => 2,
                'path' => $parentTask->id.'/'.$subParent->id.'/',
                'is_leaf' => true,
                'sort_order' => $i,
            ]);
        }
        $subParent->update(['is_leaf' => false]);

        // ── More hierarchical trees for richer demo ─────────────────

        // "Create mood board" → children
        $moodBoard = $tasks[5]; // Create mood board (Riverside)
        $moodChildren = ['Collect inspiration images', 'Define color palette', 'Select texture patterns'];
        foreach ($moodChildren as $i => $title) {
            Task::create([
                'user_id' => $userId,
                'project_id' => $moodBoard->project_id,
                'goal_id' => $moodBoard->goal_id,
                'parent_id' => $moodBoard->id,
                'title' => $title,
                'status' => 'done',
                'priority' => 'medium',
                'depth' => 1,
                'path' => $moodBoard->id.'/',
                'is_leaf' => true,
                'sort_order' => $i,
            ]);
        }
        $moodBoard->update(['is_leaf' => false]);

        // "Design logo options" → children with sub-children
        $logoTask = $tasks[6]; // Design logo options (Riverside)
        $logoChildren = [];
        foreach (['Sketch initial concepts', 'Digital drafts', 'Client revision rounds'] as $i => $title) {
            $logoChildren[] = Task::create([
                'user_id' => $userId,
                'project_id' => $logoTask->project_id,
                'goal_id' => $logoTask->goal_id,
                'parent_id' => $logoTask->id,
                'title' => $title,
                'status' => $i === 0 ? 'done' : ($i === 1 ? 'in-progress' : 'todo'),
                'priority' => 'high',
                'depth' => 1,
                'path' => $logoTask->id.'/',
                'is_leaf' => true,
                'sort_order' => $i,
                'time_estimate_minutes' => [60, 120, 90][$i],
            ]);
        }
        $logoTask->update(['is_leaf' => false]);

        // "Digital drafts" → sub-children
        $digitalDrafts = $logoChildren[1];
        foreach (['Wordmark version', 'Icon mark version', 'Combined lockup'] as $i => $title) {
            Task::create([
                'user_id' => $userId,
                'project_id' => $digitalDrafts->project_id,
                'parent_id' => $digitalDrafts->id,
                'title' => $title,
                'status' => $i === 0 ? 'done' : 'in-progress',
                'priority' => 'high',
                'depth' => 2,
                'path' => $logoTask->id.'/'.$digitalDrafts->id.'/',
                'is_leaf' => true,
                'sort_order' => $i,
                'time_estimate_minutes' => 90,
            ]);
        }
        $digitalDrafts->update(['is_leaf' => false]);

        // "Design database schema" → SaaS MVP tree
        $dbSchema = $tasks[12]; // Design database schema (SaaS MVP)
        $dbChildren = [];
        foreach (['Define entity relationships', 'Write migration files', 'Create seed data', 'Peer review schema'] as $i => $title) {
            $dbChildren[] = Task::create([
                'user_id' => $userId,
                'project_id' => $dbSchema->project_id,
                'goal_id' => $dbSchema->goal_id,
                'parent_id' => $dbSchema->id,
                'title' => $title,
                'status' => 'done',
                'priority' => 'critical',
                'depth' => 1,
                'path' => $dbSchema->id.'/',
                'is_leaf' => true,
                'sort_order' => $i,
            ]);
        }
        $dbSchema->update(['is_leaf' => false]);

        // "Build user authentication" → SaaS MVP auth tree
        $authTask = $tasks[13]; // Build user authentication (SaaS MVP)
        $authChildren = [];
        foreach (['Set up OAuth providers', 'Build login/register forms', 'Implement JWT tokens', 'Add role-based permissions'] as $i => $title) {
            $authChildren[] = Task::create([
                'user_id' => $userId,
                'project_id' => $authTask->project_id,
                'goal_id' => $authTask->goal_id,
                'parent_id' => $authTask->id,
                'title' => $title,
                'status' => $i < 2 ? 'done' : ($i === 2 ? 'in-progress' : 'todo'),
                'priority' => 'critical',
                'depth' => 1,
                'path' => $authTask->id.'/',
                'is_leaf' => true,
                'sort_order' => $i,
                'time_estimate_minutes' => [120, 180, 90, 120][$i],
            ]);
        }
        $authTask->update(['is_leaf' => false]);

        // "Set up OAuth providers" → sub-children
        $oauthTask = $authChildren[0];
        foreach (['Google OAuth setup', 'GitHub OAuth setup', 'Apple Sign-In setup'] as $i => $title) {
            Task::create([
                'user_id' => $userId,
                'project_id' => $oauthTask->project_id,
                'parent_id' => $oauthTask->id,
                'title' => $title,
                'status' => 'done',
                'priority' => 'high',
                'depth' => 2,
                'path' => $authTask->id.'/'.$oauthTask->id.'/',
                'is_leaf' => true,
                'sort_order' => $i,
            ]);
        }
        $oauthTask->update(['is_leaf' => false]);

        // "Get contractor quotes" → Home Renovation tree
        $contractorTask = $tasks[17]; // Get contractor quotes (Home Reno)
        foreach (['Research local contractors', 'Schedule site visits', 'Compare estimates', 'Check references'] as $i => $title) {
            Task::create([
                'user_id' => $userId,
                'project_id' => $contractorTask->project_id,
                'goal_id' => $contractorTask->goal_id,
                'parent_id' => $contractorTask->id,
                'title' => $title,
                'status' => 'done',
                'priority' => 'high',
                'depth' => 1,
                'path' => $contractorTask->id.'/',
                'is_leaf' => true,
                'sort_order' => $i,
            ]);
        }
        $contractorTask->update(['is_leaf' => false]);

        // Set deferral fields on deferred tasks
        $tasks[23]->update([
            'deferral_reason' => 'priority',
            'deferral_note' => 'Focusing on client work first',
            'revisit_date' => now()->addDays(30),
        ]);
        $tasks[24]->update([
            'deferral_reason' => 'budget',
            'deferral_note' => 'Need to evaluate email platforms',
            'revisit_date' => now()->addDays(14),
        ]);

        // ── Habits ────────────────────────────────────────────────────
        $habits = [];
        // DB: title (not name), frequency (daily/weekdays/weekly/custom),
        // time_of_day (morning/afternoon/evening/anytime), status (active/paused)
        $habitData = [
            ['Morning Meditation', 'daily', 'Health', 'morning', 45, 52],
            ['Exercise', 'weekdays', 'Health', 'morning', 22, 30],
            ['Read 30 minutes', 'daily', 'Growth', 'evening', 38, 42],
            ['Journal Writing', 'daily', 'Creative', 'evening', 15, 20],
            ['Deep Work Block', 'weekdays', 'Business', 'morning', 28, 35],
            ['Drink 8 glasses of water', 'daily', 'Health', 'anytime', 50, 55],
            ['Review finances', 'weekly', 'Finance', 'afternoon', 8, 12],
            ['Family dinner together', 'weekdays', 'Family', 'evening', 18, 22],
        ];

        foreach ($habitData as [$title, $freq, $area, $timeOfDay, $current, $best]) {
            $habits[] = Habit::create([
                'user_id' => $userId,
                'life_area_id' => $areas[$area]->id,
                'title' => $title,
                'frequency' => $freq,
                'target_days' => $freq === 'custom' ? [1, 3, 5] : null,
                'time_of_day' => $timeOfDay,
                'status' => 'active',
                'streak_current' => $current,
                'streak_best' => $best,
                'started_at' => now()->subDays(rand(30, 180)),
            ]);
        }

        // ── Habit Logs ────────────────────────────────────────────────
        foreach ($habits as $habit) {
            $daysBack = rand(20, 35);
            for ($d = 0; $d < $daysBack; $d++) {
                $date = now()->subDays($d);
                if (rand(0, 100) < 80) {
                    $status = rand(0, 100) < 75 ? 'completed' : (['skipped', 'missed'][rand(0, 1)]);
                    HabitLog::create([
                        'habit_id' => $habit->id,
                        'logged_date' => $date->toDateString(),
                        'status' => $status,
                        'note' => $d < 3 ? ['Great session', 'Felt focused', 'Quick but effective', null][rand(0, 3)] : null,
                    ]);
                }
            }
        }

        // ── Daily Plans ───────────────────────────────────────────────
        $dailyPlans = [];
        $themes = ['Deep Work Day', 'Client Day', 'Creative Sprint', 'Admin & Planning', 'Learning Day', 'Review Day', 'Build Day'];
        $intentions = [
            'Focus on shipping the Acme homepage redesign',
            'Complete the Riverside branding presentation',
            'Make significant progress on the SaaS MVP auth system',
            'Catch up on administrative tasks and emails',
            'Dedicate morning to AWS certification study',
            'Review all project statuses and plan next week',
            'Build out portfolio case studies',
        ];
        for ($d = 13; $d >= 0; $d--) {
            $date = now()->subDays($d);
            $plan = DailyPlan::create([
                'user_id' => $userId,
                'plan_date' => $date->toDateString(),
                'day_theme' => $themes[$d % count($themes)],
                'morning_intention' => $intentions[$d % count($intentions)],
                'top_priority_1' => $tasks[rand(0, min(5, count($tasks) - 1))]->id,
                'top_priority_2' => $tasks[rand(6, min(12, count($tasks) - 1))]->id,
                'top_priority_3' => $tasks[rand(13, min(20, count($tasks) - 1))]->id,
                'energy_rating' => $d === 0 ? null : rand(2, 5),
                'focus_rating' => $d === 0 ? null : rand(2, 5),
                'progress_rating' => $d === 0 ? null : rand(2, 5),
                'evening_reflection' => $d === 0 ? null : 'Productive day overall. '.['Made good progress on key tasks.', 'Some interruptions but recovered well.', 'Need to improve focus tomorrow.', 'Great flow state in the morning.'][$d % 4],
                'status' => $d === 0 ? 'active' : 'reviewed',
            ]);
            $dailyPlans[] = $plan;
        }

        // ── Time Blocks ───────────────────────────────────────────────
        $blockTypes = ['deep-work', 'meeting', 'admin', 'buffer', 'personal'];
        foreach ($dailyPlans as $plan) {
            $hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17];
            $numBlocks = rand(4, 7);
            for ($b = 0; $b < $numBlocks; $b++) {
                if (! isset($hours[$b])) {
                    break;
                }
                $start = $hours[$b];
                $duration = rand(1, 2);
                $type = $blockTypes[rand(0, 4)];
                TimeBlock::create([
                    'daily_plan_id' => $plan->id,
                    'task_id' => rand(0, 1) ? $tasks[rand(0, min(20, count($tasks) - 1))]->id : null,
                    'title' => match ($type) {
                        'deep-work' => ['Design work', 'Coding session', 'Writing', 'Research'][rand(0, 3)],
                        'meeting' => ['Client standup', 'Team sync', 'Review meeting', '1-on-1'][rand(0, 3)],
                        'admin' => ['Email', 'Invoicing', 'Planning', 'Organizing'][rand(0, 3)],
                        'buffer' => ['Lunch', 'Walk break', 'Coffee break', 'Stretch'][rand(0, 3)],
                        'personal' => ['Meditation', 'Reading', 'Exercise', 'Family time'][rand(0, 3)],
                    },
                    'block_type' => $type,
                    'start_time' => sprintf('%02d:00', $start),
                    'end_time' => sprintf('%02d:00', $start + $duration),
                    'color_hex' => match ($type) {
                        'deep-work' => '#3B82F6',
                        'meeting' => '#9333EA',
                        'admin' => '#6B7280',
                        'buffer' => '#22C55E',
                        'personal' => '#F59E0B',
                    },
                ]);
            }
        }

        // ── Journal Entries ───────────────────────────────────────────
        // DB entry_type enum: morning, evening, weekly, freeform
        // DB mood: unsignedTinyInteger 1-5
        $entryTypes = ['morning', 'evening', 'weekly', 'freeform'];
        for ($j = 0; $j < 12; $j++) {
            JournalEntry::create([
                'user_id' => $userId,
                'entry_date' => now()->subDays($j * 2),
                'entry_type' => $entryTypes[$j % count($entryTypes)],
                'content' => $this->journalContent($j),
                'mood' => rand(1, 5),
                'tags' => $this->journalTags($j),
            ]);
        }

        // ── Weekly Reviews ────────────────────────────────────────────
        for ($w = 0; $w < 4; $w++) {
            $weekStart = now()->subWeeks($w)->startOfWeek();
            WeeklyReview::create([
                'user_id' => $userId,
                'week_start_date' => $weekStart->toDateString(),
                'wins' => "- Completed {$this->randomWin()}\n- Made progress on {$this->randomWin()}\n- Started {$this->randomWin()}",
                'friction' => "- Time management during client meetings\n- Staying focused in the afternoon\n- Too many context switches between projects",
                'next_week_focus' => 'Ship the Acme homepage and start Riverside logo concepts',
                'overall_score' => rand(1, 5),
                'outcomes_met' => [
                    'Creative' => rand(5, 10),
                    'Business' => rand(5, 10),
                    'Health' => rand(4, 10),
                    'Family' => rand(6, 10),
                    'Growth' => rand(5, 10),
                    'Finance' => rand(4, 10),
                ],
            ]);
        }

        // ── AI Interactions ───────────────────────────────────────────
        $aiTypes = ['daily-morning', 'goal-breakdown', 'daily-evening', 'weekly', 'freeform'];
        for ($a = 0; $a < 6; $a++) {
            AiInteraction::create([
                'user_id' => $userId,
                'daily_plan_id' => $dailyPlans[$a % count($dailyPlans)]->id,
                'interaction_type' => $aiTypes[$a % count($aiTypes)],
                'prompt' => 'Generate a '.$aiTypes[$a % count($aiTypes)].' for today',
                'response' => 'Here is your personalized '.$aiTypes[$a % count($aiTypes)].': Focus on high-impact work today. Your energy is best in the morning - tackle the Acme redesign first.',
                'model_used' => 'claude-3-sonnet',
                'tokens_used' => rand(200, 800),
            ]);
        }

        // ── Meeting Notes ─────────────────────────────────────────────
        $meetings = [];
        // DB: title (required), meeting_date (date),
        // meeting_type enum: discovery/requirements/check-in/brainstorm/review/planning/retrospective/handoff/other
        // transcription_status enum: pending/processing/complete/failed
        $meetingData = [
            [$projects[0], 'external', 'check-in', 'Acme Weekly Check-in', 'Acme weekly check-in on design progress'],
            [$projects[1], 'external', 'discovery', 'Riverside Branding Kickoff', 'Riverside branding kickoff with stakeholders'],
            [$projects[3], 'self', 'planning', 'SaaS MVP Sprint Planning', 'SaaS MVP sprint planning session'],
            [$projects[0], 'external', 'review', 'Acme Design Review', 'Acme design review with marketing team'],
        ];

        foreach ($meetingData as [$proj, $clientType, $meetingType, $title, $summary]) {
            $meetings[] = MeetingNote::create([
                'user_id' => $userId,
                'project_id' => $proj->id,
                'client_type' => $clientType,
                'meeting_type' => $meetingType,
                'title' => $title,
                'meeting_date' => now()->subDays(rand(1, 14))->toDateString(),
                'attendees' => [
                    ['name' => 'Brian Cash', 'role' => 'Designer'],
                    ['name' => 'Client Contact', 'role' => 'Stakeholder'],
                    ['name' => 'Project Manager', 'role' => 'PM'],
                ],
                'summary' => $summary,
                'decisions' => 'Key decisions were made about next steps and deliverables.',
                'action_items' => 'Follow up on mockups, schedule next review session.',
                'transcript' => "Meeting transcript placeholder with key discussion points about {$proj->name}.",
                'source' => 'manual',
                'transcription_status' => 'complete',
            ]);
        }

        // ── Meeting Scope Items ───────────────────────────────────────
        $scopeTypes = ['in-scope', 'out-of-scope', 'deferred', 'assumption', 'risk'];
        foreach ($meetings as $m => $meeting) {
            for ($s = 0; $s < rand(1, 3); $s++) {
                MeetingScopeItem::create([
                    'user_id' => $userId,
                    'meeting_id' => $meeting->id,
                    'type' => $scopeTypes[rand(0, 4)],
                    'description' => ['Homepage redesign with responsive layout', 'Mobile app development', 'SEO optimization for launch', 'API integration with CRM', 'Brand color palette finalization'][$s % 5],
                    'confirmed_with_client' => (bool) rand(0, 1),
                    'notes' => 'Discussed during the meeting and agreed upon.',
                ]);
            }
        }

        // ── Meeting Done Items ────────────────────────────────────────
        $doneItems = [
            ['Delivered homepage mockups', 'Client loved the modern aesthetic', true],
            ['Completed brand audit report', 'Comprehensive analysis of current brand identity', false],
            ['Shipped responsive navigation', 'Works across all target devices', false],
            ['Presented logo concepts', 'Client selected option B with minor refinements', true],
            ['Set up staging environment', 'Fully functional preview for client review', false],
        ];

        foreach ($doneItems as $i => [$desc, $outcome, $testimonial]) {
            MeetingDoneItem::create([
                'user_id' => $userId,
                'meeting_id' => $meetings[$i % count($meetings)]->id,
                'task_id' => $tasks[$i]->id,
                'project_id' => $meetings[$i % count($meetings)]->project_id,
                'title' => $desc,
                'description' => $desc,
                'outcome' => $outcome,
                'client_reaction' => $testimonial ? 'Very positive reaction from the client team' : null,
                'value_delivered' => rand(500, 5000),
                'save_as_testimonial' => $testimonial,
                'client_quote' => $testimonial ? 'This is exactly what we were looking for. Great work!' : null,
            ]);
        }

        // ── Meeting Resource Signals ──────────────────────────────────
        $signalTypes = ['budget', 'time', 'technology', 'capability', 'readiness'];
        for ($r = 0; $r < 4; $r++) {
            MeetingResourceSignal::create([
                'user_id' => $userId,
                'meeting_id' => $meetings[$r % count($meetings)]->id,
                'resource_type' => $signalTypes[$r],
                'description' => ['Client mentioned potential budget increase for Q2', 'Timeline shifted to accommodate new requirements', 'Need to evaluate new design tool', 'May need additional developer resources'][$r],
                'client_quote' => '"We might have additional budget available next quarter"',
                'creates_revisit_opportunity' => (bool) rand(0, 1),
            ]);
        }

        // ── Meeting Agendas ───────────────────────────────────────────
        for ($ag = 0; $ag < 3; $ag++) {
            $agenda = MeetingAgenda::create([
                'user_id' => $userId,
                'project_id' => $projects[$ag]->id,
                'meeting_note_id' => $meetings[$ag]->id,
                'title' => "Agenda for {$projects[$ag]->name} meeting",
                'client_type' => $ag < 2 ? 'external' : 'self',
                'client_name' => $projects[$ag]->client_name,
                'scheduled_for' => now()->addDays(rand(1, 7)),
                'purpose' => 'Review progress and plan next steps',
                'desired_outcomes' => ['Align on priorities', 'Review deliverables', 'Plan next sprint'],
                'status' => 'draft',
            ]);

            // Add agenda items
            $itemTitles = ['Review progress since last meeting', 'Discuss blockers and risks', 'Demo latest changes', 'Plan next sprint', 'Action item review'];
            foreach ($itemTitles as $ii => $itemTitle) {
                AgendaItem::create([
                    'agenda_id' => $agenda->id,
                    'title' => $itemTitle,
                    'description' => "Discussion point: {$itemTitle}",
                    'item_type' => ['topic', 'action-followup', 'topic', 'topic', 'action-followup'][$ii],
                    'time_allocation_minutes' => [10, 5, 15, 10, 5][$ii],
                    'sort_order' => $ii,
                    'status' => 'pending',
                ]);
            }
        }

        // ── Task Quality Gates ────────────────────────────────────────
        for ($q = 0; $q < 4; $q++) {
            TaskQualityGate::create([
                'user_id' => $userId,
                'task_id' => $tasks[$q]->id,
                'status' => ['passed', 'passed', 'pending', 'failed'][$q],
                'failure_reason' => $q === 3 ? 'Responsive issues on mobile need to be addressed' : null,
                'checklist' => [
                    ['question' => 'Code review completed?', 'answer' => $q < 2 ? 'Yes' : null, 'passed' => $q < 2],
                    ['question' => 'Tests pass?', 'answer' => $q < 2 ? 'Yes' : null, 'passed' => $q < 2],
                    ['question' => 'Client approved?', 'answer' => $q === 0 ? 'Yes' : null, 'passed' => $q === 0],
                ],
                'triggered_at' => now()->subDays(rand(1, 10)),
                'reviewed_at' => $q < 3 ? now()->subDays(rand(0, 5)) : null,
                'reviewer_notes' => $q === 3 ? 'Need to fix responsive issues on mobile' : null,
                'children_completed' => rand(2, 5),
                'children_total' => rand(3, 8),
            ]);
        }

        // ── Deferred Items ────────────────────────────────────────────
        // DB status enum: someday, scheduled, in-review, promoted, proposed, won, lost, archived
        // DB deferral_reason enum: budget, timeline, priority, client-not-ready, scope-control, awaiting-decision, technology, personal
        // DB opportunity_type enum: phase-2, upsell, upgrade, new-project, retainer, product-feature, personal-goal, personal-development, none
        $deferredData = [
            ['Mobile app version of portfolio', 'budget', 'Not enough bandwidth this quarter', 'product-feature', 'external', 2000, 'someday'],
            ['Advanced analytics dashboard', 'timeline', 'Client wants to focus on core features first', 'upsell', 'external', 5000, 'scheduled'],
            ['Video content strategy', 'priority', 'Focusing on written content first', 'new-project', 'self', 3000, 'in-review'],
            ['Automated email sequences', 'technology', 'Need to evaluate platforms', 'product-feature', 'self', 1500, 'someday'],
            ['Client onboarding portal', 'client-not-ready', 'Client still defining requirements', 'phase-2', 'external', 8000, 'promoted'],
            ['SEO audit service offering', 'scope-control', 'Out of scope for current engagement', 'retainer', 'external', 4000, 'proposed'],
        ];

        $deferredItems = [];
        foreach ($deferredData as [$title, $reason, $note, $oppType, $clientType, $value, $status]) {
            $deferredItems[] = DeferredItem::create([
                'user_id' => $userId,
                'title' => $title,
                'description' => "Deferred item: {$title}. {$note}",
                'deferral_reason' => $reason,
                'opportunity_type' => $oppType,
                'client_type' => $clientType,
                'estimated_value' => $value,
                'status' => $status,
                'deferred_on' => now()->subDays(rand(5, 30)),
                'revisit_date' => now()->addDays(rand(7, 60)),
                'review_count' => rand(0, 3),
            ]);
        }

        // ── Opportunity Pipeline ──────────────────────────────────────
        $stages = ['identified', 'qualifying', 'nurturing', 'proposing', 'negotiating'];
        for ($o = 0; $o < 4; $o++) {
            OpportunityPipeline::create([
                'user_id' => $userId,
                'deferred_item_id' => $deferredItems[$o]->id,
                'project_id' => $projects[$o % count($projects)]->id,
                'title' => $deferredItems[$o]->title.' - Opportunity',
                'client_name' => $projects[$o % count($projects)]->client_name ?? 'Personal',
                'stage' => $stages[$o],
                'estimated_value' => $deferredItems[$o]->estimated_value,
                'probability_percent' => [20, 40, 60, 80, 90][$o],
                'next_action' => ['Research scope', 'Prepare proposal', 'Follow up with client', 'Send contract', 'Schedule kickoff'][$o],
                'next_action_date' => now()->addDays(rand(3, 21)),
            ]);
        }

        // ── Deferral Reviews ──────────────────────────────────────────
        for ($dr = 0; $dr < 3; $dr++) {
            DeferralReview::create([
                'user_id' => $userId,
                'deferred_item_id' => $deferredItems[$dr]->id,
                'reviewed_on' => now()->subDays(rand(1, 14)),
                'outcome' => ['keep-someday', 'reschedule', 'promote'][$dr],
                'next_revisit_date' => now()->addDays(rand(7, 30)),
                'review_notes' => 'Reviewed and determined current status is appropriate.',
            ]);
        }

        // ── Project Budgets ───────────────────────────────────────────
        // DB: budget_type (fixed/hourly/retainer), NO status column
        foreach ([0, 1, 3] as $idx => $pIdx) {
            ProjectBudget::create([
                'user_id' => $userId,
                'project_id' => $projects[$pIdx]->id,
                'budget_type' => ['hourly', 'fixed', 'retainer'][$idx],
                'budget_total' => [15000, 8000, 5000][$idx],
                'hourly_rate' => $idx === 0 ? 150 : null,
                'estimated_hours' => [100, 60, 40][$idx],
                'actual_spend' => [4500, 3200, 2000][$idx],
                'estimated_remaining' => [10500, 4800, 3000][$idx],
                'burn_rate' => [30, 40, 40][$idx],
                'alert_threshold_percent' => 80,
                'notes' => ['Hourly billing with monthly invoicing', 'Fixed price engagement', 'Monthly retainer agreement'][$idx],
            ]);
        }

        // ── Time Entries ──────────────────────────────────────────────
        $timeDescriptions = ['Design work', 'Development', 'Client call', 'Research', 'Testing', 'Documentation'];
        for ($t = 0; $t < 18; $t++) {
            $projIdx = $t % count($projects);
            $taskIdx = $t % count($tasks);
            $hours = round(rand(5, 40) / 10, 1);
            $rate = rand(0, 1) ? 150 : 0;
            TimeEntry::create([
                'user_id' => $userId,
                'project_id' => $projects[$projIdx]->id,
                'task_id' => $tasks[$taskIdx]->id,
                'description' => $timeDescriptions[$t % 6],
                'hours' => $hours,
                'logged_date' => now()->subDays(rand(0, 14)),
                'billable' => $rate > 0,
                'hourly_rate' => $rate > 0 ? $rate : null,
                'cost' => round($hours * $rate, 2),
            ]);
        }
    }

    private function journalContent(int $index): string
    {
        $contents = [
            "Today was a productive day. I managed to complete the homepage mockups for Acme and got positive feedback. The morning meditation really helped set the tone.\n\nKey insight: When I start the day with intention, everything flows better.",
            "Grateful for:\n1. A supportive partner who understands my work schedule\n2. The beautiful weather that motivated my morning run\n3. A breakthrough moment on the SaaS authentication design",
            "Lesson learned: Don't try to multitask during deep work sessions. I noticed my quality drops significantly when I switch between coding and responding to messages.",
            'New idea: What if we offered a design audit as a productized service? Could charge a flat rate and deliver a comprehensive report. Need to think about pricing and scope.',
            'Stream of consciousness morning pages. Feeling energized today. Had a great workout. Thinking about the upcoming client meeting with Riverside Studio. Need to prepare the logo concepts presentation.',
            "Reflecting on this week's progress. The Acme project is on track, but I need to allocate more time to the SaaS MVP. Maybe I can shift some afternoon blocks.",
            "Gratitude journal: The small wins matter. Completed a 45-day meditation streak today. It doesn't seem like much, but consistency is the foundation of all progress.",
            'Had an interesting conversation about remote work and productivity. Key takeaway: environment design matters more than willpower.',
            'Brainstorming session for the newsletter. Topics: design systems, freelancer productivity, client communication frameworks.',
            'Weekly reflection: Good week overall. Met most of my targets. Need to improve evening routine - too much screen time before bed.',
            "The power of saying no. Turned down a project that didn't align with my goals. Felt uncomfortable but right.",
            'Morning pages: dreaming about the next phase of the business. What would it look like to have 2-3 ongoing retainer clients?',
        ];

        return $contents[$index % count($contents)];
    }

    private function journalTags(int $index): array
    {
        $tagSets = [
            ['productivity', 'work', 'acme'],
            ['gratitude', 'personal'],
            ['lesson', 'focus', 'productivity'],
            ['idea', 'business', 'service'],
            ['morning-pages', 'energy'],
            ['reflection', 'planning'],
            ['gratitude', 'habits', 'meditation'],
            ['insight', 'remote-work'],
            ['brainstorm', 'content', 'newsletter'],
            ['reflection', 'weekly-review'],
            ['boundaries', 'business'],
            ['morning-pages', 'vision'],
        ];

        return $tagSets[$index % count($tagSets)];
    }

    private function randomWin(): string
    {
        $wins = [
            'Acme homepage redesign',
            'Riverside logo concepts',
            'AWS study session',
            '10-mile training run',
            'newsletter issue #5',
            'portfolio case study',
            'SaaS auth module',
            'client proposal',
            'morning meditation streak',
            'family dinner streak',
        ];

        return $wins[rand(0, count($wins) - 1)];
    }
}
