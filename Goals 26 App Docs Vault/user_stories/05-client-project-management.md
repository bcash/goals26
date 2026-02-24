# Client Project Management

**As a** Solas Run user, **I want** to manage an external client project with tasks, budget tracking, and time entries, **so that** I keep the project profitable, on schedule, and within scope while tracking every hour of work.

## Scenario

Brian has won a new web redesign project for Acme Corp. He navigates to Goals & Projects > Projects and clicks "Create." He fills in the project name "Acme Corp Website Redesign," selects "Business" as the Life Area, enters "Acme Corp" as the client name, sets the due date to June 30, and links it to his existing goal "Grow consulting revenue to $120k." He picks a blue project color and saves.

Next, he goes to Goals & Projects > Budgets and creates a budget for the project. He selects "Acme Corp Website Redesign" from the dropdown, sets budget type to "Fixed Price," enters $15,000 as the total budget, $150/hour as the hourly rate, and 100 as estimated hours. He sets the alert threshold to 80% so he gets a notification when he has burned through $12,000.

Brian then creates tasks for the project. He opens the TaskResource and creates "Discovery call with Acme marketing team" linked to the project, marks it as a leaf task with `two_minute_check: true`, and sets priority to high. Over the next few days he creates a task tree: "Design phase" with children "Wireframes," "Visual mockups," and "Design review meeting." Under "Development," he creates "Frontend build," "CMS integration," and "QA testing."

Each morning, Brian logs time entries as he works. He navigates to Goals & Projects > Time Entries and creates an entry: selects the project, selects the task "Wireframes," enters 3.5 hours, a description "Created wireframes for all 5 pages," marks it billable. The BudgetService automatically recalculates: actual spend is now $525, 3.5% of budget used, burn rate is $525/day for the project's one-day age.

After two weeks of work, Brian has logged 45 hours. The budget shows $6,750 spent (45%), well within the 80% alert threshold. He checks the ProjectBudget edit page to see the current status section showing Actual Spend, % Used, and Remaining in real time.

When he hits 55 hours logged, the actual spend reaches $8,250 and the burn rate trends upward. At 80 hours ($12,000 / 80%), the BudgetService triggers a persistent Filament notification: "Budget Alert: Acme Corp Website Redesign -- 80% of budget used. $3,000 remaining."

## Steps

1. Create the project in ProjectResource with client name and Life Area
2. Create a ProjectBudget with fixed-price settings and alert threshold
3. Create a hierarchical task tree for the project
4. Log daily time entries against specific tasks
5. Monitor budget status on the ProjectBudget edit page
6. Receive budget alert notification at 80% threshold

## System Features Used

- **Resources:** ProjectResource, TaskResource, ProjectBudgetResource, TimeEntryResource
- **Services:** BudgetService, TaskTreeService
- **Models:** Project, Task, ProjectBudget, TimeEntry
- **Notifications:** Filament persistent notification for budget alerts

## Expected Outcome

The client project is fully tracked with a task tree, budget guardrails, and time entries. Brian knows exactly how much time and money he has spent, what the burn rate looks like, and gets an automatic alert before overrunning the budget. Every hour of work is attributable to a specific task and project.
