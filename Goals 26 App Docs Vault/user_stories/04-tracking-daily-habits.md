# Tracking Daily Habits

**As a** Solas Run user, **I want** to log my daily habits from the dashboard checklist and watch my streak progress and habit ring fill up, **so that** I build consistent routines and get visual reinforcement of my daily discipline.

## Scenario

Brian has six active habits configured in the system: Morning Meditation (Health, morning), Journaling (Growth, morning), 30-Minute Walk (Health, afternoon), Read 20 Pages (Growth, evening), Evening Review (Growth, evening), and Gratitude Practice (Family, evening). Each habit has a frequency of "daily" and is linked to a Life Area.

At 7:15 AM, Brian opens the dashboard. The HabitRingWidget shows 0% -- a gray circle with "0/6" in the center and the message "Today is waiting for you." The MorningChecklistWidget displays all six habits grouped by time of day, with morning habits at the top. None are checked.

Brian taps the checkbox next to "Morning Meditation." The `toggleHabit()` method on the MorningChecklistWidget fires, creating a HabitLog record with `status: completed` for today. The HabitStreakService recalculates the streak: this is day 23 in a row, updating `streak_current` to 23 and `streak_best` to 23 (a new personal best). The Livewire event `habit-logged` dispatches, and the HabitRingWidget refreshes to show 17% with "1/6" in the center.

He checks off "Journaling" next. The ring jumps to 33% with "2/6." The StreakHighlightsWidget on the right side of the dashboard now shows "Morning Meditation - 23 days - Personal best!" with a star icon.

Throughout the day, Brian returns to the dashboard to check off habits as he completes them. After his afternoon walk at 2 PM, the ring shows 50% and the encouragement message changes to "Halfway there. Finish strong." By 9 PM, he has completed all six habits. The ring fills to 100% in Solas gold, and the message reads "All habits done! Exceptional day."

Brian navigates to Habits > Habit Logs to see the full log history. The table shows today's six completed entries. He can filter by habit, by status (completed/skipped/missed), and sort by date. The HabitResource table view shows his current streaks sorted descending, with Morning Meditation at 23 days leading the list.

## Steps

1. Open the Solas Run dashboard
2. View the HabitRingWidget showing 0% completion
3. Check off "Morning Meditation" in the MorningChecklistWidget
4. Observe the ring update to 17% and streak recalculate
5. Check off "Journaling" and observe cross-widget updates
6. Return throughout the day to check off remaining habits
7. Complete all habits and see the 100% celebration message
8. Navigate to Habit Logs to review the full history

## System Features Used

- **Widgets:** HabitRingWidget, MorningChecklistWidget, StreakHighlightsWidget
- **Resources:** HabitResource, HabitLogResource
- **Services:** HabitStreakService
- **Models:** Habit, HabitLog
- **Livewire Events:** `habit-logged` (cross-widget refresh)

## Expected Outcome

All six habits are logged for the day with completion timestamps. The habit ring shows 100%, streak counters are updated (with personal best flagging), and the dashboard provides continuous visual reinforcement throughout the day. The habit log table maintains a complete historical record for pattern analysis.
