# Life Area Balance Check

**As a** Solas Run user, **I want** to review my goal progress grouped by Life Area, identify which areas are being neglected, and create new goals to restore balance, **so that** I maintain a holistic approach to life and do not sacrifice one area for another.

## Scenario

During his Sunday weekly review, Brian opens the dashboard and looks at the GoalProgressWidget. It displays all active goals with progress bars colored by Life Area. He notices a pattern: Business goals (blue) are all above 60% progress, Creative (purple) has one goal at 45%, but Health (green) has two goals stuck at 15% and 20%. Family (amber) has no active goals at all.

He opens the WeeklyReviewResource form and starts scoring each Life Area. Business gets a 5 -- excellent week. Creative gets a 4 -- writing is progressing. Health gets a 2 -- he skipped walks, ate poorly, and his energy ratings have been declining all week. Family gets a 2 -- he missed his daughter's school event because of a client meeting. Growth gets a 4. Finance gets a 3.

The pattern is clear: Business and Growth are thriving at the expense of Health and Family. Brian writes in the Friction section: "Health is suffering because I'm overloading my schedule with client work. Family time is being sacrificed for deadlines that could have been managed differently."

In the Next Week Focus field, he writes: "No meetings after 3 PM. Block 30 minutes at lunch for walks. Attend every family event this week."

Brian then navigates to Goals & Projects > Goals and clicks "Create" to add a Family goal: "Weekly family adventure day" with a 90-day horizon. He links it to the Family Life Area and sets the "why" to: "Because these years with my daughter at this age will never come back."

He also edits his Health goal "Maintain consistent exercise routine" and creates new tasks under it: "Schedule walks in daily time blocks" and "Prep healthy meals on Sunday." He marks both as daily actions so they appear in the MorningChecklistWidget.

On the dashboard, the GoalProgressWidget now shows goals across all six Life Areas. The imbalance is visible but being addressed. The weekly review AI analysis notes: "Your Health and Family scores have declined for three consecutive weeks. Consider protecting time blocks for these areas with the same rigor you apply to client deadlines."

## Steps

1. Review the GoalProgressWidget on the dashboard, noting Life Area distribution
2. Open the WeeklyReviewResource and score each Life Area
3. Identify neglected areas (Health at 2, Family at 2)
4. Write reflection on friction and imbalance
5. Set next week's focus with specific protective actions
6. Create a new goal in the Family Life Area
7. Add new tasks to the Health goal
8. Mark health tasks as daily actions
9. Review the AI weekly analysis for pattern confirmation

## System Features Used

- **Widgets:** GoalProgressWidget (grouped by Life Area)
- **Resources:** WeeklyReviewResource, GoalResource, TaskResource
- **Services:** AiService (weekly analysis), GoalProgressService
- **Models:** WeeklyReview, Goal, Task, LifeArea

## Expected Outcome

Brian has quantified evidence of life area imbalance through weekly scores and goal progress data. He has created concrete actions to address the neglected areas: a new Family goal, Health tasks marked as daily actions, and time-blocking commitments. The AI reinforces the pattern with multi-week trend analysis, making the imbalance impossible to ignore.
