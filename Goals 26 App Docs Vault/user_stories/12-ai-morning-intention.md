# AI Morning Intention

**As a** Solas Run user, **I want** to generate an AI-powered morning intention that considers my goals, priorities, and energy level, **so that** I start each day with a personalized, motivating statement that aligns my daily actions with my larger purpose.

## Scenario

Brian opens the dashboard at 7 AM on a Wednesday. The DayThemeWidget shows yesterday's ratings: Energy 3, Focus 2, Progress 4. Yesterday was a good output day but focus was scattered and energy was average. He has already set today's theme to "Restore & Rebuild" and selected his three priorities.

He scrolls down to the AiIntentionWidget, which shows "Your AI morning intention hasn't been generated yet." The widget displays explanatory text: it will draw from active goals, recent reflections, and today's priorities.

Brian clicks the "Generate Morning Intention" button. The button changes to a loading state with a spinning indicator and the text "Generating..." The widget displays a message: "Solas Run is reading your goals and crafting today's intention..."

Behind the scenes, the AiService `generateMorningIntention()` method fires. It builds a context snapshot that includes: today's day theme ("Restore & Rebuild"), the three priority tasks, yesterday's energy/focus/progress ratings, the most recent evening reflection (which mentioned email interruptions), active goals with their progress percentages, current habit streaks, and the "why" statements from his top goals.

The AI produces a personalized intention: "Today is about restoring the energy your ambitions need. Yesterday's focus was challenged -- this morning, protect your deep work blocks by batching email to 11 AM and 4 PM only. Your meditation streak at 24 days is building real capacity. Use that calm center to ship the Acme wireframes and add another page to chapter 9. The Spanish practice at lunch keeps the Growth area moving. Remember: you are building systems that run themselves, so the daily discipline is the product."

The intention appears in a gold-bordered blockquote on the dashboard. An AiInteraction record is stored with `interaction_type: daily-morning`, the full context JSON, the prompt, the response, and the token count.

If Brian wants a different perspective, he can click the small "Regenerate" link in the widget header. The system will run a fresh AI call with the same context but introduce slight prompt variation for a different angle.

## Steps

1. Open the Solas Run dashboard in the morning
2. Review yesterday's ratings in the DayThemeWidget
3. Ensure today's theme and priorities are set
4. Click "Generate Morning Intention" in the AiIntentionWidget
5. Wait for the AI to process the context and generate the intention
6. Read the personalized intention in the blockquote
7. Optionally click "Regenerate" for a different perspective

## System Features Used

- **Widgets:** AiIntentionWidget, DayThemeWidget
- **Services:** AiService (generateMorningIntention)
- **Models:** DailyPlan, Goal, Habit, AiInteraction
- **Resources:** AiInteractionResource (audit log)

## Expected Outcome

A deeply personalized morning intention is generated that references Brian's specific goals, yesterday's challenges, current streaks, and today's priorities. The intention is displayed prominently on the dashboard and stored as an AI interaction record. The context-aware message helps Brian feel aligned and motivated, transforming a generic morning into a purposeful one.
