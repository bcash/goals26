# Morning Planning Routine

**As a** Solas Run user, **I want** to open the dashboard each morning and quickly orient my day with a theme, priorities, and habit checklist, **so that** I start every day with clarity and intention rather than reactive chaos.

## Scenario

It is 6:45 AM on a Tuesday. Brian opens Solas Run in his browser and lands on the Daily Command Center. The DayThemeWidget at the top shows yesterday's energy (3/5), focus (4/5), and progress (4/5) ratings. Today's theme is blank. He clicks the theme stat card, which opens a modal, and types "Deep Focus" as today's theme. The system creates or updates today's DailyPlan record with the theme.

Next, Brian looks at the MorningChecklistWidget. His Top 3 priorities are not yet set, so the widget shows an empty state with a link to the DailyPlan edit page. He clicks "Set your Top 3" and navigates to the DailyPlan form. In the Morning Session section, he writes a morning intention: "Ship the task decomposition feature and protect the afternoon for creative writing." He selects three tasks from the searchable dropdown for his top priorities: "Write TaskTreeService unit tests," "Review client proposal for Acme," and "Outline chapter 9 of the novel."

Back on the dashboard, the MorningChecklistWidget now shows his three priorities with checkboxes. Below them, the habit checklist displays his six active habits grouped by time of day: morning meditation, journaling, 30-minute walk, reading, evening review, and gratitude practice. The morning habits (meditation, journaling) appear first. Brian taps the checkbox next to "Morning meditation" -- the HabitStreakService recalculates the streak and the HabitRingWidget updates from 0% to 17%.

He scrolls down to the AiIntentionWidget, which shows "Not yet generated." He clicks "Generate Morning Intention" and the AiService builds a prompt using his active goals, yesterday's reflection, and today's priorities. A moment later, a personalized intention appears in a gold-bordered blockquote.

Finally, he glances at the TimeBlockTimelineWidget on the right showing his scheduled blocks: a deep-work session from 7-9 AM, a client call at 10, admin from 11-12, and a personal writing block from 2-4 PM. The 7 AM block is highlighted with "NOW" indicator.

## Steps

1. Open the Solas Run dashboard at `/admin`
2. Review yesterday's ratings in the DayThemeWidget
3. Click the theme stat card and enter "Deep Focus" as the day theme
4. Click "Set your Top 3" link in the MorningChecklistWidget
5. Fill in morning intention and select three priority tasks in the DailyPlan edit form
6. Save and return to the dashboard
7. Check off morning habits directly from the MorningChecklistWidget
8. Click "Generate Morning Intention" in the AiIntentionWidget
9. Review the time block schedule in the TimeBlockTimelineWidget

## System Features Used

- **Widgets:** DayThemeWidget, MorningChecklistWidget, HabitRingWidget, AiIntentionWidget, TimeBlockTimelineWidget, StreakHighlightsWidget
- **Resources:** DailyPlanResource, TaskResource, HabitResource
- **Services:** DailyPlanService, HabitStreakService, AiService
- **Models:** DailyPlan, Task, Habit, HabitLog, TimeBlock

## Expected Outcome

Brian's day is fully planned within 10 minutes. He has a theme, an AI intention, three concrete priorities, habits tracked, and a visual schedule. The dashboard reflects the morning session as complete, and he can begin his first deep-work block with full clarity on what matters today.
