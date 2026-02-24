# Meeting Prep with Agenda Builder

**As a** Solas Run user, **I want** to build a structured meeting agenda that automatically includes open action items and deferred items ready for revisit, **so that** every meeting is focused, productive, and surfaces the right conversations at the right time.

## Scenario

Brian has a client check-in with Acme Corp scheduled for Thursday at 2 PM. On Wednesday evening, he opens Goals & Projects > Meeting Agendas and clicks "Create." He enters the title "Acme Corp Bi-Weekly Check-in," selects "External Client" as the meeting type, enters "Acme Corp" as the client name, picks the "Acme Corp Website Redesign" project, and sets the scheduled date/time.

In the Purpose field he writes: "Review wireframe progress, discuss homepage feedback, and address the deferred mobile app feature." He adds two desired outcomes as tags: "Wireframe approval" and "Decision on mobile scope."

After saving, the AgendaService automatically runs three population steps. First, `addStandingItems()` creates two agenda items: "Purpose & desired outcomes" (5 min) and "Budget & timeline check" (5 min). Second, `addOpenActionItems()` queries tasks for the Acme project that are in-progress or todo, and adds three action-followup items: "Follow-up: Finalize color palette," "Follow-up: Get copy for hero section," and "Follow-up: Schedule design review with stakeholders."

Third, `addDeferredReviewItems()` finds one deferred item linked to the Acme project that is due for review: "Mobile app companion version" -- deferred three months ago because the client said it was beyond the current budget. This gets added as a "deferred-review" agenda item with the note "Trigger: Q1 budget cycle begins."

Finally, `suggestTopics()` calls the AI, which reviews previous Acme meetings and the stated purpose, and suggests three additional topics: "Homepage hero section animation approach" (10 min), "Content migration timeline from old CMS" (10 min), and "QA testing plan and browser support scope" (5 min). These appear in the AI Suggested Topics section.

Brian reviews the agenda in the AgendaItemsRelationManager, reorders items by dragging, adjusts time allocations, and adds one manual topic: "Invoice status -- two invoices outstanding." He changes the agenda status to "Ready" and has everything prepared for the meeting.

## Steps

1. Navigate to Meeting Agendas and click "Create"
2. Enter meeting title, client type, project, and scheduled time
3. Write the meeting purpose and desired outcomes
4. Save the agenda to trigger automatic population
5. Review the auto-populated standing items, action follow-ups, and deferred review items
6. Review AI-suggested topics in the suggestions section
7. Add a manual topic via the AgendaItemsRelationManager
8. Reorder and adjust time allocations
9. Set status to "Ready"

## System Features Used

- **Resources:** MeetingAgendaResource, AgendaItemsRelationManager
- **Services:** AgendaService (buildAgenda, addStandingItems, addOpenActionItems, addDeferredReviewItems, suggestTopics)
- **Models:** MeetingAgenda, AgendaItem, Task, DeferredItem, Project
- **AI Integration:** Agenda topic suggestion prompt

## Expected Outcome

A complete, structured agenda exists for the Acme meeting with standing items, open action follow-ups, a deferred item ready for revisit, and AI-suggested topics. Brian walks into the meeting fully prepared, with a time-boxed plan that ensures the deferred mobile app opportunity is raised at the right moment.
