# Decomposing a Complex Task

**As a** Solas Run user, **I want** to break down a large, ambiguous task into actionable subtasks using the AI-guided decomposition interview, **so that** every piece of work in my system passes the 2-minute test or has a clearly defined scope before I schedule it.

## Scenario

Brian is looking at his task tree and sees a task called "Build the marketing website" sitting at depth 1 under the root goal "Launch Solas Run to 500 users." The task has `decomposition_status: needs_breakdown` and `two_minute_check: false`. It is clearly too large to act on directly.

He opens the Task Tree page and clicks the "Break down" link that appears on hover next to the task. This redirects him to the Decomposition Interview page. The DecompositionInterviewService fires its `start()` method, which sends context to the AI -- including the task title, its depth in the tree, the ancestor chain ("Launch Solas Run > Acquire users > Build the marketing website"), the linked project name, and the goal title.

The AI responds with a first question: "What are the key sections or pages this marketing website needs to have?" Brian types his answer: "Home page, features page, pricing page, about page, and a blog." The AI processes this via the `answer()` method and returns a verdict of `needs_children` with five suggested subtasks: "Design and build home page," "Design and build features page," "Design and build pricing page," "Design and build about page," and "Set up blog with CMS integration."

Brian reviews the suggestions, edits "Set up blog with CMS integration" to "Set up blog on Ghost CMS," and clicks "Accept." The `acceptSubtasks()` method calls `TaskTreeService::addChild()` for each subtask. The parent task's `is_leaf` flag flips to `false`, and five new leaf tasks are created at depth 2 with materialized paths.

Brian notices that "Design and build home page" is still too big. He clicks "Break down" on it, and the interview asks: "What specific elements does the home page need?" He answers: "Hero section with headline, feature highlight grid, testimonial carousel, and a CTA section." The AI suggests four subtasks, which he accepts. The home page task is no longer a leaf, and four new leaf tasks at depth 3 are created.

For each of the depth-3 tasks, the AI determines they pass the 2-minute test or are single focused work sessions. Each gets `two_minute_check: true` and `decomposition_status: ready`.

## Steps

1. Navigate to the Task Tree page
2. Locate the "Build the marketing website" task
3. Click "Break down" to start the Decomposition Interview
4. Answer the AI's question about website sections
5. Review the five suggested subtasks
6. Edit one subtask title and click "Accept"
7. Click "Break down" on "Design and build home page"
8. Answer the follow-up question about home page elements
9. Accept the four leaf-level subtasks
10. Verify each leaf task shows "Ready" status in the tree

## System Features Used

- **Pages:** TaskTree, DecompositionInterview
- **Services:** DecompositionInterviewService, TaskTreeService, AiService
- **Models:** Task (self-referential tree with parent_id, depth, path, is_leaf)
- **AI Integration:** goal-breakdown interaction type

## Expected Outcome

The originally ambiguous "Build the marketing website" task is now a structured tree with two levels of children. All leaf nodes pass the 2-minute test or have defined outputs, are marked as `ready`, and can be scheduled on a daily plan. The parent tasks automatically track decomposition status, and the task tree view shows the full hierarchy with proper indentation.
