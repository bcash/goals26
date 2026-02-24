# Task Tree Navigation

**As a** Solas Run user, **I want** to navigate the hierarchical task tree, expand and collapse nodes, filter by project, and complete leaf tasks inline, **so that** I can see the full structure of my work and act on individual items without losing context of the bigger picture.

## Scenario

Brian navigates to the Task Tree page via Goals & Projects > Task Tree. The page loads with all root-level tasks (depth 0) displayed. Each root represents a BHAG or major initiative. He sees three roots: "Launch Solas Run to 500 users," "Acme Corp Website Redesign," and "Publish my science fiction novel."

He uses the project filter dropdown at the top of the page and selects "Acme Corp Website Redesign." The tree reloads showing only tasks linked to this project. The root node displays with a target icon and the title in bold. Below it, indented by 1.5rem, are the children: "Design phase," "Development," and "QA & Launch."

Brian clicks on "Design phase" to expand it, revealing three children at depth 2: "Wireframes" (done, shown with strikethrough and a green checkmark), "Visual mockups" (in-progress, shown in amber), and "Design review meeting" (todo, shown in gray). Each leaf node has a small completion circle that Brian can click to mark it done.

He expands "Development" and sees four tasks. Three are leaf nodes marked as "Ready" with a small checkmark badge visible on hover. One -- "Frontend build" -- still shows "Break down" on hover because it has `two_minute_check: false`. Brian clicks "Break down" and is redirected to the Decomposition Interview to break this task into smaller pieces.

Returning to the tree after decomposition, "Frontend build" now shows three children: "Build homepage components," "Build features page layout," and "Build responsive navigation." Each is a ready leaf node.

Brian completes "Build homepage components" by clicking the circle next to it. The TaskTreeService `completeLeaf()` method fires, marks the task as done, and checks if all siblings under "Frontend build" are complete. They are not yet, so the parent stays in progress. The tree visually updates: the completed task shows strikethrough text with a green checkmark.

A quality gate badge appears on "Design phase" because all its children are now complete (wireframes, mockups approved, review meeting done). The badge reads "Review Required." Brian will handle this in a separate quality gate review session.

## Steps

1. Navigate to Goals & Projects > Task Tree
2. Use the project filter to select a specific project
3. Expand root nodes to see children at each depth level
4. Identify leaf nodes that are ready to work on
5. Click "Break down" on tasks that need decomposition
6. Complete leaf tasks by clicking the completion circle
7. Observe parent task status updates as children are completed
8. Notice quality gate badges when all children are done

## System Features Used

- **Pages:** TaskTree (custom Filament page)
- **Services:** TaskTreeService (getTree, completeLeaf, propagateUpward)
- **Models:** Task (parent_id, depth, path, is_leaf, sort_order, quality_gate_status)
- **Views:** task-tree-node.blade.php (recursive partial)

## Expected Outcome

Brian can see the complete hierarchical structure of his work, filter by project, expand nodes to any depth, complete individual leaf tasks inline, and trigger decomposition on items that are not yet broken down. The tree provides a bird's-eye view that connects daily leaf-level actions to the highest-level goals.
