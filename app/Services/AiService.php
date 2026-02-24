<?php

namespace App\Services;

use App\Models\AiInteraction;
use App\Models\DailyPlan;
use App\Models\DeferredItem;
use App\Models\Goal;
use App\Models\LifeArea;
use App\Models\Project;
use App\Models\User;
use App\Models\WeeklyReview;
use Illuminate\Support\Facades\Log;

class AiService
{
    /**
     * Generate a morning intention based on today's plan, active goals, and recent reflections.
     */
    public function generateMorningIntention(DailyPlan $plan): string
    {
        $user = auth()->user();

        $activeGoals = Goal::where('status', 'active')
            ->with('lifeArea')
            ->get()
            ->map(fn ($g) => "- [{$g->lifeArea?->name}] {$g->title} ({$g->progress_percent}%)")
            ->join("\n");

        $priorities = collect([
            $plan->priority1?->title,
            $plan->priority2?->title,
            $plan->priority3?->title,
        ])->filter()->map(fn ($t, $i) => ($i + 1) . ". {$t}")->join("\n");

        $prompt = <<<PROMPT
You are a personal productivity coach writing a morning intention for today.

USER: {$user->name}
DATE: {$plan->plan_date->format('l, F j, Y')}
DAY THEME: {$plan->day_theme}

ACTIVE GOALS:
{$activeGoals}

TODAY'S PRIORITIES:
{$priorities}

Write a brief, inspiring morning intention (2-3 sentences) that:
1. Acknowledges the day's theme and priorities
2. Connects today's work to their larger goals
3. Provides a motivating, grounded perspective

Be warm but direct. No fluff. Speak to someone who values clarity and purpose.
PROMPT;

        return $this->callAi($prompt, 'daily-morning', [
            'daily_plan_id' => $plan->id,
        ]);
    }

    /**
     * Generate a goal breakdown — suggested milestones and first actions.
     */
    public function generateGoalBreakdown(Goal $goal): string
    {
        $prompt = <<<PROMPT
You are a strategic planning advisor. Break down this goal into actionable milestones.

GOAL: "{$goal->title}"
LIFE AREA: {$goal->lifeArea?->name}
HORIZON: {$goal->horizon}
WHY: {$goal->why}
TARGET DATE: {$goal->target_date?->format('M j, Y') ?? 'Not set'}
DESCRIPTION: {$goal->description}

Create a structured breakdown:
1. 3-5 milestones (ordered chronologically)
2. For each milestone, suggest 2-3 concrete first actions
3. Identify what resources or capabilities are needed
4. Flag any dependencies between milestones

Be specific and actionable. Each action should pass the 2-minute test or be a defined work block.
PROMPT;

        return $this->callAi($prompt, 'goal-breakdown', [
            'goal_id' => $goal->id,
        ]);
    }

    /**
     * Generate a daily plan for the user based on active goals, tasks, and habits.
     */
    public function generateDailyPlan(User $user): string
    {
        $activeGoals = $user->goals()
            ->where('status', 'active')
            ->with('lifeArea')
            ->get()
            ->map(fn ($g) => "- [{$g->lifeArea?->name}] {$g->title}")
            ->join("\n");

        $pendingTasks = $user->tasks()
            ->whereIn('status', ['todo', 'in-progress'])
            ->where('is_leaf', true)
            ->orderBy('priority', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($t) => "- [{$t->priority}] {$t->title}" . ($t->due_date ? " (due: {$t->due_date->format('M j')})" : ''))
            ->join("\n");

        $dayOfWeek = now()->format('l');

        $prompt = <<<PROMPT
You are a daily planning assistant. Create a focused plan for today.

USER: {$user->name}
DAY: {$dayOfWeek}, {$today = now()->format('F j, Y')}

ACTIVE GOALS:
{$activeGoals}

PENDING TASKS (by priority):
{$pendingTasks}

Create a daily plan that includes:
1. A suggested day theme (one word or short phrase)
2. Top 3 priorities for the day (from the task list)
3. A suggested time block schedule (morning, afternoon, evening)
4. A brief morning intention

Be realistic about what can be accomplished in one day. Protect energy and include buffer time.
PROMPT;

        return $this->callAi($prompt, 'daily-morning');
    }

    /**
     * Generate an evening summary reflecting on the day's accomplishments.
     */
    public function generateEveningSummary(DailyPlan $plan): string
    {
        $priorities = collect([
            $plan->priority1,
            $plan->priority2,
            $plan->priority3,
        ])->filter()->map(fn ($t) => "- {$t->title}: {$t->status}")->join("\n");

        $prompt = <<<PROMPT
You are a reflective evening coach. Write a brief evening summary for today.

DATE: {$plan->plan_date->format('l, F j')}
DAY THEME: {$plan->day_theme}
MORNING INTENTION: {$plan->morning_intention}

PRIORITIES AND STATUS:
{$priorities}

EVENING RATINGS:
- Energy: {$plan->energy_rating}/5
- Focus: {$plan->focus_rating}/5
- Progress: {$plan->progress_rating}/5

EVENING REFLECTION: {$plan->evening_reflection}

Write a brief (2-3 sentence) evening summary that:
1. Acknowledges what was accomplished
2. Notes patterns in energy/focus
3. Suggests one thing to carry forward to tomorrow

Be honest and encouraging. Celebrate wins without ignoring gaps.
PROMPT;

        return $this->callAi($prompt, 'daily-evening', [
            'daily_plan_id' => $plan->id,
        ]);
    }

    /**
     * Generate a weekly analysis from the review data.
     */
    public function generateWeeklyAnalysis(WeeklyReview $review): string
    {
        $outcomesJson = json_encode($review->outcomes_met ?? [], JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are a weekly review analyst. Analyze this week's performance.

WEEK OF: {$review->week_start_date->format('M j, Y')}

WINS:
{$review->wins}

FRICTION:
{$review->friction}

OUTCOMES BY AREA:
{$outcomesJson}

OVERALL SCORE: {$review->overall_score}/5

Write a strategic weekly analysis (3-4 paragraphs) covering:
1. Key patterns — what worked and what didn't
2. Energy and focus trends — where was momentum strongest?
3. Goal alignment — are daily actions connecting to larger goals?
4. Specific recommendation for next week's focus

Be direct and actionable. This person values clarity over comfort.
PROMPT;

        return $this->callAi($prompt, 'weekly');
    }

    /**
     * Freeform chat with optional context.
     */
    public function freeformChat(string $prompt, ?string $context = null): string
    {
        $fullPrompt = $context
            ? "CONTEXT:\n{$context}\n\nUSER REQUEST:\n{$prompt}"
            : $prompt;

        return $this->callAi($fullPrompt, 'freeform');
    }

    /**
     * Generate a brainstorm for a life area — new goal ideas and opportunities.
     */
    public function generateGoalBrainstorm(LifeArea $lifeArea): string
    {
        $existingGoals = Goal::where('life_area_id', $lifeArea->id)
            ->get()
            ->map(fn ($g) => "- {$g->title} ({$g->status}, {$g->progress_percent}%)")
            ->join("\n");

        $prompt = <<<PROMPT
You are a life coach and strategic thinking partner. Brainstorm new goal ideas for this life area.

LIFE AREA: {$lifeArea->name}
DESCRIPTION: {$lifeArea->description}

EXISTING GOALS IN THIS AREA:
{$existingGoals}

Generate 3-5 fresh goal ideas that:
1. Build on existing momentum (completed or near-complete goals)
2. Address gaps (areas with no active goals)
3. Are specific enough to be actionable
4. Include a suggested horizon (90-day, 1-year, 3-year, lifetime)
5. Include a "why" statement for each

Be creative but grounded. Challenge assumptions. Suggest at least one ambitious stretch goal.
PROMPT;

        return $this->callAi($prompt, 'goal-breakdown');
    }

    /**
     * Assess resource readiness for a project or personal goal.
     */
    public function assessResourceReadiness(Project $project): string
    {
        $taskCount = $project->tasks()->count();
        $doneCount = $project->tasks()->where('status', 'done')->count();
        $deferredCount = $project->tasks()->where('status', 'deferred')->count();

        $prompt = <<<PROMPT
You are a project resource analyst. Assess the resource readiness for this project.

PROJECT: {$project->name}
STATUS: {$project->status}
CLIENT: {$project->client_name}
DUE DATE: {$project->due_date?->format('M j, Y') ?? 'Not set'}
DESCRIPTION: {$project->description}

TASK METRICS:
- Total tasks: {$taskCount}
- Completed: {$doneCount}
- Deferred: {$deferredCount}
- In progress or pending: {$inProgress = $taskCount - $doneCount - $deferredCount}

Assess:
1. Is this project on track given the task completion rate?
2. What resource risks exist (time, budget, capability)?
3. Are there too many deferred items suggesting scope issues?
4. What should be prioritized in the next sprint?

Be specific and practical. Flag risks clearly.
PROMPT;

        return $this->callAi($prompt, 'freeform');
    }

    /**
     * Analyze a meeting transcript and extract structured intelligence.
     * Used by MeetingIntelligenceService.
     */
    public function analyzeMeetingScope(\App\Models\ClientMeeting $meeting): string
    {
        $prompt = <<<PROMPT
Analyze the following client meeting transcript and extract scope intelligence.

PROJECT: {$meeting->project?->name}
MEETING TYPE: {$meeting->meeting_type}
DATE: {$meeting->meeting_date->format('M j, Y')}

TRANSCRIPT:
{$meeting->transcript}

Extract and categorize the following:
1. IN-SCOPE items -- things the client explicitly said are included
2. OUT-OF-SCOPE items -- things the client explicitly excluded
3. ASSUMPTIONS -- things the team assumed but the client didn't confirm
4. RISKS -- anything that could jeopardize budget, timeline, or quality
5. ACTION ITEMS -- concrete next steps mentioned

For each item, include a direct quote from the transcript if possible.

Format your response as a structured summary a project manager would use
to define project boundaries and protect the budget.
PROMPT;

        return $this->callAi($prompt, 'freeform', [
            'meeting_id' => $meeting->id,
            'project_id' => $meeting->project_id,
        ]);
    }

    /**
     * Analyze a deferred opportunity or personal goal.
     */
    public function analyzeOpportunity(DeferredItem $item): string
    {
        if ($item->client_type === 'self') {
            return $this->analyzePersonalGoal($item);
        }

        $prompt = <<<PROMPT
You are a business development advisor. Analyze this deferred client opportunity
and write a brief opportunity profile.

ITEM: "{$item->title}"
CLIENT: {$item->client_name}
DEFERRED REASON: {$item->deferral_reason}
OPPORTUNITY TYPE: {$item->opportunity_type}
CLIENT CONTEXT: {$item->client_context}
CLIENT QUOTE: {$item->client_quote}
ESTIMATED VALUE: \${$item->estimated_value}
PROJECT CONTEXT: {$item->project?->name}

Write a 3-4 paragraph opportunity brief covering:
1. Why this opportunity exists and what the client's underlying need is
2. What the right timing and trigger for this conversation looks like
3. How to re-open the conversation naturally -- without being pushy
4. What a compelling proposal would focus on

Be specific and actionable. Speak to someone who knows the client well.
PROMPT;

        $response = $this->callAi($prompt, 'freeform', [
            'deferred_item_id' => $item->id,
            'opportunity_type' => $item->opportunity_type,
        ]);

        $item->update(['ai_opportunity_analysis' => $response]);

        return $response;
    }

    /**
     * Analyze a deferred personal goal with resource assessment.
     */
    private function analyzePersonalGoal(DeferredItem $item): string
    {
        $resourcesJson = json_encode($item->resource_requirements ?? [], JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are a personal development advisor. Analyze this personal goal and write a
personal readiness assessment covering when and how to pursue it.

GOAL: "{$item->title}"
DESCRIPTION: {$item->description}
WHY IT MATTERS: {$item->why_it_matters}
DEFERRED REASON: {$item->deferral_reason}
OPPORTUNITY TYPE: {$item->opportunity_type}

RESOURCE CONSTRAINTS:
{$resourcesJson}

Write a 3-4 paragraph assessment covering:
1. What resources are blocking this goal and when they might become available
2. What prerequisite skills or capabilities need to develop first
3. What milestones or life events would signal it's the right time
4. How to prepare or maintain momentum while waiting for the right moment

Be encouraging and practical. Help this person see the path forward.
PROMPT;

        $response = $this->callAi($prompt, 'freeform', [
            'deferred_item_id' => $item->id,
            'opportunity_type' => $item->opportunity_type,
            'client_type' => 'self',
        ]);

        $item->update(['ai_opportunity_analysis' => $response]);

        return $response;
    }

    /**
     * General chat method used by other services.
     * Wraps callAi with a simpler signature.
     */
    public function chat(string $prompt, string $type = 'freeform', array $context = []): string
    {
        return $this->callAi($prompt, $type, $context);
    }

    /**
     * Central AI call method.
     * Currently returns simulated responses since no AI API key is configured.
     * When ready, replace the body with an HTTP call to OpenAI/Anthropic.
     */
    private function callAi(string $prompt, string $type = 'freeform', array $context = []): string
    {
        // Log the interaction
        $interaction = AiInteraction::create([
            'user_id' => auth()->id(),
            'interaction_type' => $type,
            'prompt' => $prompt,
            'context_json' => $context ?: null,
            'model_used' => 'simulated',
            'daily_plan_id' => $context['daily_plan_id'] ?? null,
            'goal_id' => $context['goal_id'] ?? null,
        ]);

        // Simulated response based on interaction type
        $response = $this->simulateResponse($type, $prompt);

        // Update the interaction with the response
        $interaction->update([
            'response' => $response,
            'tokens_used' => strlen($prompt) + strlen($response),
        ]);

        return $response;
    }

    /**
     * Generate a realistic simulated response based on the interaction type.
     * This will be replaced with actual AI API calls when configured.
     */
    private function simulateResponse(string $type, string $prompt): string
    {
        return match ($type) {
            'daily-morning' => $this->simulateMorningIntention(),
            'daily-evening' => $this->simulateEveningSummary(),
            'weekly' => $this->simulateWeeklyAnalysis(),
            'goal-breakdown' => $this->simulateGoalBreakdown($prompt),
            default => $this->simulateFreeformResponse($prompt),
        };
    }

    private function simulateMorningIntention(): string
    {
        $intentions = [
            "Today is about depth over breadth. Your top priorities align with your bigger creative vision -- give them the focused attention they deserve. Let the smaller tasks wait until the afternoon when your energy naturally shifts to execution mode.",
            "This morning, anchor yourself in your 'why.' The tasks on your plate today aren't just items to check off -- they're building blocks toward the life you're designing. Start with the hardest priority first while your focus is sharp.",
            "Today's theme calls you to balance ambition with presence. Your priorities are well-chosen -- tackle them in order and trust that consistent daily action compounds into remarkable progress over time.",
        ];

        return $intentions[array_rand($intentions)];
    }

    private function simulateEveningSummary(): string
    {
        return "Today showed solid progress on your core priorities. Your energy pattern suggests morning deep work sessions are your sweet spot -- protect that time tomorrow. The gap between what you planned and what you completed is normal; carry the unfinished priority forward as tomorrow's first task rather than adding it to a growing backlog.";
    }

    private function simulateWeeklyAnalysis(): string
    {
        return "This week revealed a clear pattern: your strongest output happens when you honour your morning creative blocks and resist the pull of reactive admin work. Your Business area saw the most progress, while Health goals slipped -- a common trade-off that needs intentional correction.\n\nThe friction you noted around context-switching is significant. Consider batching your meeting days and protecting at least two mornings per week as meeting-free deep work zones.\n\nYour goal alignment is strong on paper but the daily execution gap suggests your task breakdown needs attention. Several of your 'in-progress' items have been there for over a week -- they may need decomposition into smaller, more actionable pieces.\n\nFor next week: Focus on completing rather than starting. Set a cap of 3 active work-in-progress items and finish each before picking up the next. This constraint will paradoxically increase your total output.";
    }

    private function simulateGoalBreakdown(string $prompt): string
    {
        // Check if this is a decomposition interview prompt (JSON expected)
        if (str_contains($prompt, 'Respond ONLY in this JSON format')) {
            return json_encode([
                'question' => 'What is the most important outcome this task needs to deliver? Can you describe what "done" looks like in concrete terms?',
                'suggested_subtasks' => [],
                'is_ready' => false,
                'rationale' => 'The task needs clarification before it can be broken down into actionable subtasks.',
            ]);
        }

        // Check if this is a quality gate checklist request
        if (str_contains($prompt, 'quality review checklist')) {
            return json_encode([
                ['question' => 'Does the output match what was originally requested?', 'answer' => null, 'passed' => null],
                ['question' => 'Has the work been reviewed against the stated requirements?', 'answer' => null, 'passed' => null],
                ['question' => 'Are there any loose ends or undocumented decisions?', 'answer' => null, 'passed' => null],
                ['question' => 'Has this been tested or verified in context?', 'answer' => null, 'passed' => null],
                ['question' => 'Would you be confident presenting this to the client or stakeholder?', 'answer' => null, 'passed' => null],
            ]);
        }

        return "Here is a structured breakdown for your goal:\n\n**Milestone 1: Foundation (Weeks 1-2)**\n- Define success criteria and measurable outcomes\n- Audit current resources and identify gaps\n- Create a project timeline with key checkpoints\n\n**Milestone 2: Core Development (Weeks 3-6)**\n- Build the primary deliverable in iterative cycles\n- Conduct weekly progress reviews against the timeline\n- Adjust scope based on resource reality\n\n**Milestone 3: Refinement & Testing (Weeks 7-8)**\n- Review all deliverables against success criteria\n- Gather feedback from stakeholders or self-assessment\n- Polish and optimize based on feedback\n\n**Milestone 4: Launch & Integration (Weeks 9-10)**\n- Deploy or integrate the completed work\n- Document lessons learned\n- Set up maintenance or follow-up rhythms\n\n**Resources needed:** Dedicated focus blocks (minimum 2 hours), relevant tools and access, accountability check-ins.\n\n**Key dependency:** Milestone 2 cannot begin until Milestone 1 success criteria are clearly defined.";
    }

    private function simulateFreeformResponse(string $prompt): string
    {
        // Check for agenda topic suggestion prompts
        if (str_contains($prompt, 'agenda topics') || str_contains($prompt, 'Suggest 3-5 specific agenda topics')) {
            return json_encode([
                [
                    'title' => 'Progress review on current deliverables',
                    'description' => 'Review what has been completed since the last meeting and confirm it meets expectations.',
                    'time_allocation_minutes' => 10,
                ],
                [
                    'title' => 'Timeline and milestone check',
                    'description' => 'Verify the project is on track and address any scheduling concerns.',
                    'time_allocation_minutes' => 10,
                ],
                [
                    'title' => 'Open questions and blockers',
                    'description' => 'Address any decisions that need to be made or information that is missing.',
                    'time_allocation_minutes' => 15,
                ],
                [
                    'title' => 'Scope confirmation for next phase',
                    'description' => 'Clarify what is included in the upcoming work and what is explicitly out of scope.',
                    'time_allocation_minutes' => 10,
                ],
            ]);
        }

        // Check for meeting intelligence extraction prompts
        if (str_contains($prompt, 'project intelligence analyst') && str_contains($prompt, 'TRANSCRIPT')) {
            return json_encode([
                'summary' => 'The meeting covered project progress, identified two completed deliverables, and deferred one feature request to a future phase due to budget constraints.',
                'decisions' => "- Approved the current design direction\n- Agreed to defer the advanced reporting feature to Phase 2\n- Confirmed the launch date remains on track",
                'done_items' => [
                    [
                        'title' => 'Homepage redesign',
                        'outcome' => 'Client approved the new design direction',
                        'outcome_metric' => null,
                        'client_quote' => 'This looks great, exactly what we were hoping for.',
                        'value_delivered' => null,
                        'save_as_testimonial' => true,
                    ],
                ],
                'action_items' => [
                    [
                        'title' => 'Prepare wireframes for the dashboard section',
                        'assigned_to' => 'user',
                        'due' => now()->addDays(5)->toDateString(),
                        'priority' => 'high',
                    ],
                    [
                        'title' => 'Provide content for the about page',
                        'assigned_to' => 'client',
                        'due' => now()->addDays(7)->toDateString(),
                        'priority' => 'medium',
                    ],
                ],
                'scope_items' => [
                    [
                        'description' => 'Dashboard section with analytics overview',
                        'type' => 'in-scope',
                        'client_quote' => 'Yes, the dashboard is definitely part of this phase.',
                        'confirmed_with_client' => true,
                    ],
                    [
                        'description' => 'Advanced reporting and export features',
                        'type' => 'deferred',
                        'client_quote' => 'We love the idea but it is not in this budget cycle.',
                        'confirmed_with_client' => true,
                    ],
                ],
                'deferred_items' => [
                    [
                        'title' => 'Advanced reporting and export features',
                        'reason' => 'budget',
                        'opportunity_type' => 'phase-2',
                        'client_quote' => 'We love the idea but it is not in this budget cycle.',
                        'estimated_value' => 8000,
                        'revisit_trigger' => 'After Q1 budget resets',
                        'why_it_matters' => 'Client expressed strong interest; natural extension of current work.',
                    ],
                ],
                'resource_signals' => [
                    [
                        'resource_type' => 'budget',
                        'description' => 'Client indicated current budget is fully allocated for this phase.',
                        'client_quote' => 'Our budget for this quarter is committed.',
                        'constraint_timeline' => 'Q1 next year',
                    ],
                ],
                'scope_analysis' => 'The project scope is well-defined with the homepage redesign and dashboard as confirmed deliverables. The advanced reporting feature has been explicitly deferred to Phase 2 due to budget constraints, representing an estimated $8,000 future opportunity. No significant risks were identified, though content delivery from the client side should be monitored to avoid timeline delays.',
            ]);
        }

        // Check for scope check prompts
        if (str_contains($prompt, 'IN SCOPE or OUT OF SCOPE')) {
            return json_encode([
                'verdict' => 'in-scope',
                'reason' => 'This task aligns with the confirmed project deliverables discussed in recent meetings.',
            ]);
        }

        // Default freeform response
        return "Based on my analysis of your current situation, here are my observations and recommendations:\n\n1. **Current State:** Your workload is well-distributed across life areas, with Business and Creative receiving the most attention. This aligns with your stated priorities.\n\n2. **Opportunity:** There are several deferred items in your pipeline that could be revisited this quarter, particularly those tied to client relationships where the timing constraint was budget-related.\n\n3. **Recommendation:** Focus on completing your current active projects before taking on new commitments. Your task completion rate suggests you may benefit from breaking larger tasks into smaller, more actionable pieces using the decomposition interview.\n\n4. **Watch out for:** The tendency to add new goals before fully processing existing ones. Quality of completion matters more than quantity of starts.";
    }
}
