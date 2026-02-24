# Creative Project Tracking

**As a** Solas Run user, **I want** to manage a creative project like writing a novel with milestones and task decomposition, **so that** my creative ambitions receive the same structured attention as my client work.

## Scenario

Brian is writing a science fiction novel. He has a goal in the Creative Life Area: "Publish my science fiction novel" with a 3-year horizon. The goal has four milestones: "Complete Act 1 (Chapters 1-8)," "Complete Act 2 (Chapters 9-18)," "Complete Act 3 (Chapters 19-25)," and "First draft complete -- begin editing."

He has created a project called "Sci-Fi Novel: The Last Signal" linked to this goal and the Creative Life Area. The project has no client name (it is a personal project) and a purple color matching the Creative area.

Brian navigates to the Task Tree page and filters by this project. The root task is "Write the complete first draft." Under it, three parent tasks align with the milestones. "Write Act 1" has eight leaf tasks, one per chapter. Six are complete (shown with strikethrough), one is in-progress ("Write Chapter 7: The Discovery"), and one is todo ("Write Chapter 8: The Confrontation").

Today, Brian wants to make progress on Chapter 7. He opens his DailyPlan and adds a time block: "Novel writing -- Chapter 7" from 2-4 PM, block type "deep-work," linked to the "Write Chapter 7" task and the novel project. The block appears on the TimeBlockTimelineWidget in green (deep-work color).

During his writing session, Brian works for two hours and completes the chapter. He returns to the task tree and clicks the completion circle next to "Write Chapter 7." The TaskTreeService marks it done. Now only Chapter 8 remains under Act 1.

Brian then starts the Decomposition Interview for "Write Chapter 8" -- not because it needs breaking down, but to clarify the chapter's scope. The AI asks: "What is the key scene or turning point in Chapter 8?" Brian answers: "The protagonist confronts the AI entity and discovers it's been protecting humanity, not threatening it." The AI determines this is a single focused writing session and marks it as "ready."

When Brian eventually completes Chapter 8, all children of "Write Act 1" will be done, triggering a quality gate. The gate asks questions like: "Does Act 1 establish the protagonist's central conflict?" and "Are all foreshadowing elements for Act 2 planted?"

The GoalProgressWidget on the dashboard shows the novel goal progressing: "Publish my science fiction novel -- 32%" with the Creative purple bar filling.

## Steps

1. Navigate to the Task Tree filtered by the novel project
2. Review the chapter-by-chapter breakdown under each act
3. Add a deep-work time block for the writing session
4. Complete the chapter and mark the leaf task as done
5. Use the Decomposition Interview to scope the next chapter
6. Monitor progress via the GoalProgressWidget

## System Features Used

- **Pages:** TaskTree
- **Resources:** ProjectResource, TaskResource, DailyPlanResource (TimeBlocksRelationManager)
- **Widgets:** GoalProgressWidget, TimeBlockTimelineWidget
- **Services:** TaskTreeService, DecompositionInterviewService, QualityGateService
- **Models:** Project, Task, Goal, Milestone, TimeBlock

## Expected Outcome

The novel project is tracked with the same rigor as client work: a hierarchical task tree with chapter-level leaf tasks, milestones per act, deep-work time blocks for writing sessions, and quality gates that ensure each act meets narrative standards before moving on. Progress is visible on the dashboard alongside business goals.
