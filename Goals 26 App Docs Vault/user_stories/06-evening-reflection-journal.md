# Evening Reflection and Journal

**As a** Solas Run user, **I want** to complete the evening section of my daily plan, write a journal entry, and rate my mood and energy, **so that** I close the day with reflection and create a rich dataset for weekly review and AI insights.

## Scenario

It is 9:30 PM and Brian is winding down. He opens Solas Run and navigates to Today > Daily Plans, then clicks "Edit" on today's plan. He scrolls past the Morning Session (already completed) to the Evening Session section.

He rates today's energy at 4 out of 5 -- it was a productive day with good sleep the night before. Focus gets a 3 -- the afternoon was interrupted by unexpected emails. Progress gets a 5 -- he shipped the task decomposition feature and completed his creative writing block.

In the Evening Reflection textarea, Brian writes: "Shipped the decomposition interview feature today. The AI-guided breakdown feels natural. Lost focus after lunch when three client emails came in -- need to batch email processing to protect deep work blocks. The writing session from 2-4 PM was excellent; chapter 9 is drafted. Grateful for the quiet afternoon."

He sets the plan status to "Reviewed" and saves. The AI Evening Summary placeholder, which had shown "Not yet generated," will populate after the AiService processes the evening context.

Next, Brian navigates to Journal > Journal and clicks "Create." He selects today's date, sets the entry type to "Evening," and rates his mood at 4 (Good). In the markdown editor, he writes a longer reflection about the day's wins, the creative writing progress, and a note about his daughter's school play next week. He adds tags: "shipping," "writing," "focus," "family." After saving, the AI insights field will generate a brief analysis connecting his journal content to his active goals.

Back on the dashboard, the DayThemeWidget will show "Deep Focus" as today's theme, and tomorrow morning when he opens the dashboard, yesterday's ratings (Energy: 4, Focus: 3, Progress: 5) will be displayed, giving him context for planning the new day.

## Steps

1. Navigate to Today > Daily Plans and edit today's plan
2. Scroll to the Evening Session section
3. Rate energy (4/5), focus (3/5), and progress (5/5)
4. Write the evening reflection in the textarea
5. Set plan status to "Reviewed" and save
6. Navigate to Journal > Journal and click "Create"
7. Select "Evening" entry type, rate mood at 4
8. Write the journal entry using the markdown editor
9. Add tags and save the entry
10. Verify ratings appear in DayThemeWidget the next morning

## System Features Used

- **Resources:** DailyPlanResource (Evening Session section), JournalEntryResource
- **Widgets:** DayThemeWidget (displays yesterday's ratings next morning)
- **Services:** AiService (generates evening summary and journal insights)
- **Models:** DailyPlan, JournalEntry
- **Components:** MarkdownEditor, TagsInput, Select (for ratings)

## Expected Outcome

The day is closed with quantified ratings and qualitative reflection. A journal entry with mood tracking, tags, and markdown content is stored. The AI will process both the evening plan data and the journal entry to generate insights. Tomorrow's dashboard will display today's ratings, creating a continuous feedback loop between days.
