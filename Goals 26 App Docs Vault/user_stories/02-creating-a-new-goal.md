# Creating a New Goal

**As a** Solas Run user, **I want** to create a new goal within a Life Area, define its horizon, add milestones, and generate initial tasks, **so that** my ambitions are captured in a structured system that connects daily actions to long-term outcomes.

## Scenario

Brian has decided to learn Spanish fluently enough to hold a 30-minute conversation by December. He navigates to Goals & Projects > Goals in the sidebar and clicks "Create Goal." In the form, he selects "Growth" as the Life Area from the searchable dropdown, which displays the book emoji and cyan color. He sets the horizon to "1 Year" and enters the title: "Achieve conversational Spanish fluency."

In the description field he writes a detailed definition of what fluency means to him. In the "Why does this matter?" field, he writes: "Because I want to connect with Maria's family in their language and model lifelong learning for my daughter." This motivation text will appear during his daily planning sessions.

He sets the target date to December 31, 2026, leaves the status as "Active," and sets progress to 0%. After saving, the system takes him to the Goal view page, which shows the MilestonesRelationManager and TasksRelationManager below the goal details.

Brian clicks "Create" in the Milestones section and adds four milestones in order: "Complete Duolingo A1 course" (due March 30), "Hold a 5-minute conversation with a native speaker" (due June 30), "Complete B1 grammar workbook" (due September 30), and "30-minute conversation sustained" (due December 15). Each milestone is created with sort_order to maintain sequence.

Next, he clicks "Create" in the Tasks section and adds his first three tasks: "Research and choose a Spanish course platform," "Set up daily 20-minute practice habit," and "Find a conversation partner on iTalki." Each task inherits the Goal's Life Area (Growth) and is linked to the first milestone.

The GoalProgressWidget on the dashboard now shows "Achieve conversational Spanish fluency" with a 0% progress bar in cyan, along with the "why" motivation text displayed in italics below.

## Steps

1. Navigate to Goals & Projects > Goals in the sidebar
2. Click "Create" to open the GoalResource form
3. Select "Growth" as the Life Area
4. Set horizon to "1 Year"
5. Enter the title, description, and "why" motivation
6. Set target date and save the goal
7. Add four milestones via the MilestonesRelationManager
8. Add three initial tasks via the TasksRelationManager
9. Return to the dashboard and confirm the goal appears in GoalProgressWidget

## System Features Used

- **Resources:** GoalResource, MilestoneResource (via RelationManager), TaskResource (via RelationManager)
- **Widgets:** GoalProgressWidget
- **Models:** Goal, Milestone, Task, LifeArea
- **Support:** LifeAreaBadge helper for dropdown options

## Expected Outcome

A fully structured goal exists in the Growth life area with four ordered milestones and three actionable tasks. The goal appears on the dashboard with a progress bar, and its "why" statement is visible during daily planning to keep motivation front and center.
