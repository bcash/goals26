# Health Goal Progress

**As a** Solas Run user, **I want** to track health goals like running and meditation using daily habits, monitor progress through the dashboard, and connect health data to energy and focus trends, **so that** I can see how physical wellness directly impacts my productivity and life quality.

## Scenario

Brian has two active goals in the Health Life Area: "Run a 10K race by September" (90-day horizon, 35% progress) and "Establish a daily meditation practice" (1-year horizon, 60% progress). Each goal has habits tied to it: a "Run 3x per week" habit (custom frequency: Monday, Wednesday, Saturday) and a "Morning Meditation" habit (daily).

On Monday morning, Brian opens the dashboard. The GoalProgressWidget shows both health goals with green progress bars. The MorningChecklistWidget displays his habits, and he checks off "Morning Meditation" first -- maintaining his rebuilt 12-day streak after the earlier break.

The HabitRingWidget updates from 0% to 17%. In the Active Streaks section of the StreakHighlightsWidget, "Morning Meditation -- 12 days" appears with "Best: 23" shown alongside it.

After his morning run, Brian checks off "Run 3x per week" in the habit checklist. The streak updates to 4 weeks consistent. He navigates to Habits > Habit Logs and adds a note to today's running log: "5K in 28:30 -- improving pace."

Brian wants to track this more granularly. He opens the task linked to his 10K goal's current milestone ("Run 5K consistently under 30 minutes") and checks the subtasks: "Complete Couch to 5K program" (done), "Run 5K three times per week for 4 weeks" (in progress -- week 3 of 4), and "Register for a local 5K race" (todo).

During his evening reflection, Brian rates his energy at 4 out of 5. He notes in the DailyPlan evening reflection: "Running in the morning is clearly boosting my energy for the rest of the day. The 7 AM run plus meditation combo seems optimal." He also writes a journal entry tagged with "health," "running," "energy" where he tracks his running data and how it correlates with focus.

Looking at the past two weeks of DailyPlan records, Brian can see a pattern: on days he ran in the morning, his energy rating averages 4.2 and focus averages 3.8. On rest days, energy averages 3.1 and focus averages 2.9. The AI weekly analysis picks up on this: "Your energy and focus ratings are significantly higher on running days. Consider adjusting your schedule to place your most demanding cognitive work on running days."

## Steps

1. Check off health-related habits on the MorningChecklistWidget
2. Add notes to habit logs for detailed tracking
3. Review health goal progress in the GoalProgressWidget
4. Complete subtasks related to running milestones
5. Rate energy in the evening DailyPlan section
6. Write journal entries connecting health to productivity
7. Review the AI weekly analysis for health-productivity correlations

## System Features Used

- **Widgets:** GoalProgressWidget, MorningChecklistWidget, HabitRingWidget, StreakHighlightsWidget
- **Resources:** HabitResource, HabitLogResource, GoalResource, TaskResource, DailyPlanResource, JournalEntryResource
- **Services:** HabitStreakService, AiService (weekly analysis)
- **Models:** Goal, Habit, HabitLog, DailyPlan, JournalEntry, Task, Milestone

## Expected Outcome

Health goals are tracked through daily habits with detailed logs, milestone-based tasks, and energy/focus correlations visible in daily plan ratings. The AI identifies patterns between health activities and productivity metrics, providing data-driven insights that motivate continued health investment. Brian can see concrete evidence that running days produce better work days.
