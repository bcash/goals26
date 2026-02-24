# Time Block Deep Work Planning

**As a** Solas Run user, **I want** to plan time blocks for the day, allocate deep work sessions, and track which blocks are active on the timeline widget, **so that** I protect focused time and can see my day's architecture at a glance.

## Scenario

It is Monday morning and Brian is planning his week's most important day. He navigates to Today > Daily Plans and edits today's plan. After setting the day theme to "Ship Week" and selecting his three priorities, he scrolls to the TimeBlocksRelationManager.

He clicks "Create" to add his first block: "Deep work: Acme homepage build" from 7:00 AM to 9:30 AM, block type "deep-work," linked to the task "Build homepage components" and the Acme project. The block gets a green color (deep-work default).

He adds more blocks:
- "Email & admin processing" from 9:30-10:00 AM, block type "admin" (gray)
- "Client call: Acme check-in" from 10:00-10:45 AM, block type "meeting" (amber), linked to the Acme project
- "Deep work: Novel writing Chapter 8" from 11:00 AM-1:00 PM, block type "deep-work" (green), linked to the writing task
- "Lunch & walk" from 1:00-2:00 PM, block type "personal" (blue)
- "Deep work: Spanish practice & learning" from 2:00-3:00 PM, block type "deep-work" (green)
- "Buffer: catch-up & planning" from 3:00-3:30 PM, block type "buffer" (light gray)
- "Family time" from 3:30 PM onward, block type "personal" (blue)

Back on the dashboard, the TimeBlockTimelineWidget displays all eight blocks in a vertical timeline. Each block shows its time range, title, and is color-coded by type. At 8:15 AM, the "Deep work: Acme homepage build" block is highlighted with a "NOW" indicator and a ring around it.

Brian can see his day at a glance: 4.5 hours of deep work (spread across three blocks), one meeting, one admin block, and protected personal time in the afternoon. The linked tasks appear as subtle descriptions under each block title.

As the day progresses, the "NOW" indicator moves to whichever block is current. When Brian clicks "Edit Plan" from the widget, he can quickly adjust blocks if the schedule needs to change -- for example, if the client call runs over, he can shorten the buffer block.

The time blocks also feed into time entry tracking. When Brian logs time against a task, he can reference the time block to verify how long he actually worked versus how long he planned.

## Steps

1. Navigate to Today > Daily Plans and edit today's plan
2. Open the TimeBlocksRelationManager
3. Create time blocks for each segment of the day
4. Assign block types (deep-work, admin, meeting, personal, buffer)
5. Link blocks to specific tasks and projects
6. Return to the dashboard to see the TimeBlockTimelineWidget
7. Monitor the "NOW" indicator as the day progresses
8. Adjust blocks if the schedule changes

## System Features Used

- **Resources:** DailyPlanResource, TimeBlocksRelationManager, TimeBlockResource
- **Widgets:** TimeBlockTimelineWidget
- **Models:** DailyPlan, TimeBlock, Task, Project
- **Views:** time-block-timeline-widget.blade.php

## Expected Outcome

Brian's day has a clear visual architecture with color-coded time blocks. Deep work sessions are protected and visible. The timeline widget provides real-time awareness of what he should be working on right now. Tasks and projects are linked to specific blocks, connecting the daily schedule to the larger project structure.
