# Financial Planning Review

**As a** Solas Run user, **I want** to manage financial goals, review investment research projects, and track saving milestones, **so that** I maintain long-term financial health alongside my business and creative pursuits.

## Scenario

Brian has a Finance Life Area with two active goals: "Build emergency fund to $25,000" (1-year horizon, 60% progress, $15,000 saved so far) and "Research and start a retirement investment portfolio" (90-day horizon, 20% progress). The Finance area uses Solas gold as its color.

He opens the GoalProgressWidget on the dashboard and sees both finance goals with their gold progress bars. The emergency fund is progressing steadily, but the investment research goal is behind schedule.

Brian navigates to Goals & Projects > Goals and clicks on "Research and start a retirement investment portfolio." The view page shows three milestones: "Read 3 investment books" (complete), "Research index fund options" (pending, due this week), and "Open brokerage account and make first investment" (pending, due in 6 weeks).

He opens the TasksRelationManager and sees the tasks under this goal: "Finish reading 'A Simple Path to Wealth'" (done), "Compare Vanguard vs. Fidelity index funds" (in-progress), "Calculate monthly investment amount from budget" (todo), and "Schedule meeting with financial advisor" (todo).

Brian creates a new task: "Review tax implications of retirement contributions" and links it to the "Research index fund options" milestone. He sets it as high priority since tax season is approaching.

For the emergency fund goal, Brian updates the progress percentage from 60% to 68% after this month's savings contribution. He clicks "Edit" on the goal and changes `progress_percent` to 68. The GoalProgressWidget on the dashboard immediately reflects the updated gold bar.

He also has a deferred financial item: "Invest in rental property" -- deferred six months ago with reason "budget" and opportunity type "personal-goal." The resource requirements show: money ($50,000 down payment), capability (real estate knowledge), and time (20 hours of research). During the weekly review, this item surfaces in the Someday/Maybe list. Brian reviews it, notes that the emergency fund is not yet complete, and reschedules the revisit date to six months out.

Brian writes a journal entry tagged "finance," "investing," "planning" capturing his current financial thinking and the decision to prioritize the emergency fund before real estate.

## Steps

1. Review finance goals on the GoalProgressWidget
2. Open the investment research goal and check milestones
3. Review and create tasks in the TasksRelationManager
4. Update emergency fund progress percentage
5. Review deferred financial items during weekly review
6. Reschedule the rental property goal
7. Write a finance-focused journal entry

## System Features Used

- **Widgets:** GoalProgressWidget
- **Resources:** GoalResource, MilestoneResource, TaskResource, DeferredItemResource, JournalEntryResource
- **Services:** DeferralService, GoalProgressService
- **Models:** Goal, Milestone, Task, DeferredItem, JournalEntry, LifeArea

## Expected Outcome

Financial goals are tracked alongside all other life areas with visible progress bars, milestones, and actionable tasks. Deferred financial aspirations are kept alive in the Someday/Maybe list with resource requirements documented. The system prevents financial goals from being forgotten while acknowledging that some financial ambitions require patience and prerequisite achievements.
