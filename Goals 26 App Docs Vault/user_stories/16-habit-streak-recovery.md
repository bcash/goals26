# Habit Streak Recovery

**As a** Solas Run user, **I want** to see when a habit streak is broken, journal about the setback, adjust the habit frequency if needed, and rebuild consistency, **so that** a broken streak becomes a learning moment rather than a reason to give up.

## Scenario

Brian has maintained a 23-day meditation streak, his longest ever. On Thursday, he had an early flight and missed his morning routine entirely. When he opens the dashboard on Friday morning, the HabitRingWidget shows 0/6 for today, but the StreakHighlightsWidget tells a different story: "Morning Meditation -- 0 days" where yesterday it showed 23.

The HabitResource table confirms it: the streak_current column for Morning Meditation shows 0, while streak_best remains 23. The MorningChecklistWidget shows the habit unchecked for both yesterday and today.

Brian feels the sting of the broken streak but decides to use Solas Run's tools to process it constructively. He navigates to Journal > Journal and creates a new freeform entry. He writes about the broken streak: what happened (early travel day), why it matters to him (the meditation practice was improving his focus scores), and what he will do differently (set a 5-minute minimum version for travel days).

He adds the tags "streak-break," "meditation," "resilience." The AI Insights field will later generate a note connecting his meditation streak to his focus rating trends in daily plans.

Next, Brian navigates to Habits > Habits and edits his Morning Meditation habit. He changes the frequency from "daily" to "custom" and unchecks Sundays as target days, giving himself a built-in rest day. He also updates the description to include: "Travel day minimum: 5-minute guided breathing on the Calm app." This way, even on disrupted days, there is a version of the habit that counts.

Back on the dashboard, Brian checks off today's meditation in the MorningChecklistWidget. The HabitStreakService recalculates: streak_current becomes 1. The StreakHighlightsWidget still shows the best streak of 23 as a benchmark to work toward. The encouraging message reads: "Good start. Don't stop now."

Over the next week, Brian rebuilds. By day 7, the streak highlight shows "Morning Meditation -- 7 days" with "Best: 23" displayed next to it. The comeback is visible and motivating.

## Steps

1. Notice the broken streak on the dashboard (StreakHighlightsWidget, HabitRingWidget)
2. Check the HabitResource table for streak details
3. Create a journal entry about the setback
4. Tag the entry with relevant labels
5. Edit the habit to adjust frequency or add a minimum version
6. Resume the habit and check it off on the dashboard
7. Watch the streak rebuild over subsequent days

## System Features Used

- **Widgets:** StreakHighlightsWidget, HabitRingWidget, MorningChecklistWidget
- **Resources:** HabitResource, JournalEntryResource, HabitLogResource
- **Services:** HabitStreakService (recalculate)
- **Models:** Habit, HabitLog, JournalEntry

## Expected Outcome

A broken streak is processed emotionally through journaling and practically through habit adjustment. The system preserves the personal best as motivation while starting the new streak from zero. The modified habit (custom days, travel minimum) is more resilient to future disruptions. Brian has a documented reflection on the setback and a concrete plan to prevent recurrence.
