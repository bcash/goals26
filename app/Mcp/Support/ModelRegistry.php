<?php

namespace App\Mcp\Support;

use App\Models\AgendaItem;
use App\Models\AiInteraction;
use App\Models\ClientMeeting;
use App\Models\CostEntry;
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

class ModelRegistry
{
    /**
     * All 26 model configurations keyed by slug.
     *
     * @return array<string, array{
     *     class: class-string,
     *     label: string,
     *     filters: array<string, array<int, string>>,
     *     searchable: array<int, string>,
     *     dates: array<int, string>,
     *     belongsTo: array<int, string>,
     *     hasTenant: bool,
     * }>
     */
    public static function all(): array
    {
        return [
            'user' => [
                'class' => User::class,
                'label' => 'User',
                'filters' => [
                    'subscription_status' => ['free', 'trial', 'active', 'cancelled', 'expired'],
                ],
                'searchable' => ['name', 'email'],
                'dates' => ['created_at', 'trial_ends_at', 'subscription_ends_at'],
                'belongsTo' => [],
                'hasTenant' => false,
            ],

            'life-area' => [
                'class' => LifeArea::class,
                'label' => 'Life Area',
                'filters' => [],
                'searchable' => ['name', 'description'],
                'dates' => ['created_at'],
                'belongsTo' => [],
                'hasTenant' => true,
            ],

            'goal' => [
                'class' => Goal::class,
                'label' => 'Goal',
                'filters' => [
                    'status' => ['active', 'completed', 'paused', 'abandoned'],
                    'horizon' => ['short', 'medium', 'long'],
                ],
                'searchable' => ['title', 'description', 'why'],
                'dates' => ['target_date', 'created_at'],
                'belongsTo' => ['lifeArea'],
                'hasTenant' => true,
            ],

            'project' => [
                'class' => Project::class,
                'label' => 'Project',
                'filters' => [
                    'status' => ['active', 'completed', 'paused', 'archived'],
                ],
                'searchable' => ['name', 'description', 'client_name', 'tech_stack', 'architecture_notes'],
                'dates' => ['due_date', 'created_at'],
                'belongsTo' => ['lifeArea', 'goal'],
                'hasTenant' => true,
            ],

            'task' => [
                'class' => Task::class,
                'label' => 'Task',
                'filters' => [
                    'status' => ['todo', 'in-progress', 'done', 'deferred', 'blocked'],
                    'priority' => ['critical', 'high', 'medium', 'low'],
                    'decomposition_status' => ['needs_breakdown', 'in_progress', 'complete'],
                    'quality_gate_status' => ['pending', 'passed', 'failed', 'needs_review'],
                ],
                'searchable' => ['title', 'notes', 'plan', 'context', 'acceptance_criteria', 'technical_requirements', 'dependencies_description'],
                'dates' => ['due_date', 'scheduled_date', 'revisit_date', 'created_at'],
                'belongsTo' => ['lifeArea', 'project', 'goal', 'milestone', 'parent'],
                'hasTenant' => true,
            ],

            'habit' => [
                'class' => Habit::class,
                'label' => 'Habit',
                'filters' => [
                    'status' => ['active', 'paused', 'archived'],
                    'frequency' => ['daily', 'weekdays', 'custom'],
                    'time_of_day' => ['morning', 'afternoon', 'evening', 'anytime'],
                ],
                'searchable' => ['title', 'description'],
                'dates' => ['started_at', 'created_at'],
                'belongsTo' => ['lifeArea'],
                'hasTenant' => true,
            ],

            'habit-log' => [
                'class' => HabitLog::class,
                'label' => 'Habit Log',
                'filters' => [
                    'status' => ['done', 'skipped', 'missed'],
                ],
                'searchable' => ['note'],
                'dates' => ['logged_date'],
                'belongsTo' => ['habit'],
                'hasTenant' => false,
            ],

            'daily-plan' => [
                'class' => DailyPlan::class,
                'label' => 'Daily Plan',
                'filters' => [
                    'status' => ['draft', 'active', 'completed', 'reviewed'],
                ],
                'searchable' => ['day_theme', 'morning_intention', 'evening_reflection'],
                'dates' => ['plan_date'],
                'belongsTo' => [],
                'hasTenant' => true,
            ],

            'time-block' => [
                'class' => TimeBlock::class,
                'label' => 'Time Block',
                'filters' => [
                    'block_type' => ['focus', 'meeting', 'admin', 'break', 'creative'],
                ],
                'searchable' => ['title', 'notes'],
                'dates' => ['created_at'],
                'belongsTo' => ['dailyPlan', 'task', 'project'],
                'hasTenant' => false,
            ],

            'journal-entry' => [
                'class' => JournalEntry::class,
                'label' => 'Journal Entry',
                'filters' => [
                    'entry_type' => ['morning', 'evening', 'reflection', 'gratitude', 'free'],
                    'mood' => ['great', 'good', 'okay', 'low', 'bad'],
                ],
                'searchable' => ['content', 'ai_insights'],
                'dates' => ['entry_date'],
                'belongsTo' => [],
                'hasTenant' => true,
            ],

            'weekly-review' => [
                'class' => WeeklyReview::class,
                'label' => 'Weekly Review',
                'filters' => [],
                'searchable' => ['wins', 'friction', 'next_week_focus', 'ai_analysis'],
                'dates' => ['week_start_date'],
                'belongsTo' => [],
                'hasTenant' => true,
            ],

            'ai-interaction' => [
                'class' => AiInteraction::class,
                'label' => 'AI Interaction',
                'filters' => [
                    'interaction_type' => ['morning_brief', 'decomposition', 'quality_gate', 'evening_review', 'scope_analysis', 'opportunity_analysis', 'general'],
                    'model_used' => ['gpt-4', 'gpt-4o', 'claude-3', 'claude-sonnet'],
                ],
                'searchable' => ['prompt', 'response'],
                'dates' => ['created_at'],
                'belongsTo' => ['dailyPlan', 'goal'],
                'hasTenant' => true,
            ],

            'client-meeting' => [
                'class' => ClientMeeting::class,
                'label' => 'Client Meeting',
                'filters' => [
                    'meeting_type' => ['kickoff', 'status', 'review', 'planning', 'retrospective', 'ad_hoc'],
                    'client_type' => ['new', 'existing', 'returning'],
                    'transcription_status' => ['pending', 'processing', 'completed', 'failed'],
                ],
                'searchable' => ['title', 'summary', 'decisions', 'action_items', 'transcript'],
                'dates' => ['meeting_date', 'transcript_received_at', 'analysis_completed_at'],
                'belongsTo' => ['project'],
                'hasTenant' => true,
            ],

            'meeting-scope-item' => [
                'class' => MeetingScopeItem::class,
                'label' => 'Meeting Scope Item',
                'filters' => [
                    'type' => ['in_scope', 'out_of_scope', 'risk', 'assumption'],
                ],
                'searchable' => ['description', 'client_quote', 'notes'],
                'dates' => ['created_at'],
                'belongsTo' => ['clientMeeting', 'task'],
                'hasTenant' => true,
            ],

            'meeting-done-item' => [
                'class' => MeetingDoneItem::class,
                'label' => 'Meeting Done Item',
                'filters' => [],
                'searchable' => ['title', 'description', 'outcome', 'client_quote'],
                'dates' => ['created_at'],
                'belongsTo' => ['meeting', 'task', 'project'],
                'hasTenant' => true,
            ],

            'meeting-resource-signal' => [
                'class' => MeetingResourceSignal::class,
                'label' => 'Meeting Resource Signal',
                'filters' => [
                    'resource_type' => ['budget', 'time', 'personnel', 'technology', 'external'],
                ],
                'searchable' => ['description', 'client_quote', 'constraint_timeline'],
                'dates' => ['created_at'],
                'belongsTo' => ['clientMeeting', 'deferredItem'],
                'hasTenant' => true,
            ],

            'meeting-agenda' => [
                'class' => MeetingAgenda::class,
                'label' => 'Meeting Agenda',
                'filters' => [
                    'status' => ['draft', 'confirmed', 'completed'],
                    'client_type' => ['new', 'existing', 'returning'],
                ],
                'searchable' => ['title', 'purpose', 'client_name', 'notes'],
                'dates' => ['scheduled_for', 'created_at'],
                'belongsTo' => ['project', 'meeting'],
                'hasTenant' => true,
            ],

            'agenda-item' => [
                'class' => AgendaItem::class,
                'label' => 'Agenda Item',
                'filters' => [
                    'item_type' => ['discussion', 'decision', 'information', 'action'],
                    'status' => ['pending', 'discussed', 'deferred', 'resolved'],
                ],
                'searchable' => ['title', 'description', 'outcome_notes'],
                'dates' => ['created_at'],
                'belongsTo' => ['meetingAgenda'],
                'hasTenant' => false,
            ],

            'task-quality-gate' => [
                'class' => TaskQualityGate::class,
                'label' => 'Task Quality Gate',
                'filters' => [
                    'status' => ['pending', 'passed', 'failed', 'needs_review'],
                ],
                'searchable' => ['reviewer_notes', 'failure_reason'],
                'dates' => ['triggered_at', 'reviewed_at'],
                'belongsTo' => ['task'],
                'hasTenant' => true,
            ],

            'deferred-item' => [
                'class' => DeferredItem::class,
                'label' => 'Deferred Item',
                'filters' => [
                    'status' => ['active', 'revisit_due', 'converted', 'archived', 'lost'],
                    'opportunity_type' => ['upsell', 'new_project', 'referral', 'partnership', 'other'],
                    'client_type' => ['new', 'existing', 'returning'],
                ],
                'searchable' => ['title', 'description', 'client_context', 'why_it_matters', 'client_name', 'client_quote'],
                'dates' => ['deferred_on', 'revisit_date', 'last_reviewed_at', 'created_at'],
                'belongsTo' => ['task', 'project', 'meeting', 'scopeItem'],
                'hasTenant' => true,
            ],

            'deferral-review' => [
                'class' => DeferralReview::class,
                'label' => 'Deferral Review',
                'filters' => [
                    'outcome' => ['keep_watching', 'revisit_soon', 'convert', 'archive', 'lost'],
                ],
                'searchable' => ['review_notes', 'context_update'],
                'dates' => ['reviewed_on', 'next_revisit_date'],
                'belongsTo' => ['deferredItem'],
                'hasTenant' => true,
            ],

            'opportunity-pipeline' => [
                'class' => OpportunityPipeline::class,
                'label' => 'Opportunity Pipeline',
                'filters' => [
                    'stage' => ['lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost'],
                ],
                'searchable' => ['title', 'description', 'client_name', 'client_email', 'notes', 'next_action', 'lost_reason'],
                'dates' => ['expected_close_date', 'actual_close_date', 'next_action_date', 'created_at'],
                'belongsTo' => ['deferredItem', 'project'],
                'hasTenant' => true,
            ],

            'project-budget' => [
                'class' => ProjectBudget::class,
                'label' => 'Project Budget',
                'filters' => [
                    'budget_type' => ['fixed', 'hourly', 'retainer', 'milestone'],
                ],
                'searchable' => ['notes'],
                'dates' => ['created_at'],
                'belongsTo' => ['project'],
                'hasTenant' => true,
            ],

            'time-entry' => [
                'class' => TimeEntry::class,
                'label' => 'Time Entry',
                'filters' => [],
                'searchable' => ['description'],
                'dates' => ['logged_date', 'created_at'],
                'belongsTo' => ['task', 'project'],
                'hasTenant' => true,
            ],

            'cost-entry' => [
                'class' => CostEntry::class,
                'label' => 'Cost Entry',
                'filters' => [
                    'category' => ['labour', 'compute', 'infrastructure', 'license', 'other'],
                ],
                'searchable' => ['description'],
                'dates' => ['logged_date', 'created_at'],
                'belongsTo' => ['project', 'task', 'clientMeeting'],
                'hasTenant' => true,
            ],

            'milestone' => [
                'class' => Milestone::class,
                'label' => 'Milestone',
                'filters' => [
                    'status' => ['pending', 'in_progress', 'completed'],
                ],
                'searchable' => ['title'],
                'dates' => ['due_date', 'created_at'],
                'belongsTo' => ['goal'],
                'hasTenant' => false,
            ],
        ];
    }

    /**
     * Get a single model config by slug.
     */
    public static function get(string $slug): ?array
    {
        return static::all()[$slug] ?? null;
    }

    /**
     * Get all registered slugs.
     *
     * @return array<int, string>
     */
    public static function slugs(): array
    {
        return array_keys(static::all());
    }
}
