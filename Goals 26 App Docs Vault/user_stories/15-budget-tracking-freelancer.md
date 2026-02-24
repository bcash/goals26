# Budget Tracking for a Freelancer

**As a** Solas Run user, **I want** to track project budgets, log time entries against specific tasks, monitor burn rate, and get alerts when approaching the budget threshold, **so that** I keep projects profitable and never surprise myself or clients with overruns.

## Scenario

Brian is three weeks into the Acme Corp Website Redesign project, which has a $15,000 fixed-price budget at $150/hour (100 estimated hours). He has been diligently logging time each day.

This morning, Brian opens Goals & Projects > Time Entries and clicks "Create." He selects "Acme Corp Website Redesign" as the project, links the entry to the task "Build homepage components," enters 4.5 hours, and writes the description "Implemented hero section, feature grid, and responsive breakpoints." He marks it as billable. The system automatically calculates the cost: 4.5 hours x $150/hour = $675.

After saving, the BudgetService `recalculate()` method fires. It sums all time entries for the project: 62 hours logged so far, $9,300 actual spend. The budget is 62% used. The burn rate calculates to $443/day based on the project's 21-day age. The estimated remaining is $5,700.

Brian navigates to Goals & Projects > Budgets and clicks "Edit" on the Acme budget. The Current Status section shows three read-only stats: Actual Spend ($9,300), % Used (62%), and Remaining ($5,700). Everything looks healthy.

Over the next two weeks, Brian continues logging time. At 78 hours logged, the actual spend reaches $11,700 (78%). The system has not yet triggered an alert because the threshold is 80%. On Friday, Brian logs 3 more hours, bringing the total to 81 hours and $12,150 actual spend. The BudgetService detects that 81% exceeds the 80% alert threshold.

A persistent Filament notification appears: "Budget Alert: Acme Corp Website Redesign -- 81.0% of budget used. $2,850 remaining." Brian sees this on his dashboard and decides to have a scope conversation with the client. He opens the project's task tree and reviews which remaining tasks are essential versus nice-to-have, using the scope items from previous meetings to justify what should be cut or deferred.

He decides to defer the "Advanced animation effects" task and captures it as a phase-2 opportunity worth $3,000. This reduces the remaining scope and helps the project close within budget.

## Steps

1. Create time entries daily against specific tasks
2. BudgetService recalculates after each entry
3. Review budget status on the ProjectBudget edit page
4. Monitor burn rate trends
5. Receive budget alert notification when threshold is crossed
6. Review remaining tasks and make scope decisions
7. Defer non-essential tasks to protect profitability

## System Features Used

- **Resources:** TimeEntryResource, ProjectBudgetResource, TaskResource, DeferredItemResource
- **Services:** BudgetService (logTime, recalculate, sendBudgetAlert), DeferralService
- **Models:** TimeEntry, ProjectBudget, Project, Task
- **Notifications:** Budget alert (persistent, database)

## Expected Outcome

Brian has a real-time view of project profitability. Every hour is tracked against specific tasks, the burn rate is monitored, and the system proactively alerts him before the budget runs out. He makes data-driven scope decisions to keep the project profitable, and deferred work becomes future pipeline value rather than lost opportunity.
