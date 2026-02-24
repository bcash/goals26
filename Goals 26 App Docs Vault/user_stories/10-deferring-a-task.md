# Deferring a Task

**As a** Solas Run user, **I want** to defer a task with full context -- capturing why it was deferred, when to revisit it, and what opportunity it represents, **so that** deferred work is never forgotten and instead becomes a structured asset in my pipeline.

## Scenario

During the Acme Corp check-in, the client mentioned wanting an analytics dashboard integrated into their website. Brian had already created a task for this: "Build analytics dashboard integration." However, the client said it would need to wait because their data team is not ready to define the metrics yet.

After the meeting, Brian opens the TaskResource list, finds the task, and clicks "Edit." He changes the status from "todo" to "deferred." This triggers additional fields to appear in the form (the deferral section). He fills in:

- **Deferral Reason:** "Client Not Ready" -- the data team needs to define metrics first
- **Deferral Note:** "Acme's data team is building their analytics strategy. Sarah mentioned they'd have metric definitions ready by end of Q1. She said 'We definitely want this, we just need to get our data house in order first.'"
- **Revisit Date:** March 31, 2026
- **Deferral Trigger:** "When Acme's data team completes their analytics strategy"
- **Has Opportunity:** Yes

He saves the task. Behind the scenes, the DeferralService `deferTask()` method executes. It updates the task with the deferral fields and creates a DeferredItem record linked to the task, the Acme project, and the client name. The opportunity type is set to "phase-2" with an estimated value of $8,000.

Because the opportunity type is commercial, the system dispatches an `AnalyzeDeferredOpportunity` job. The AI analyzes the context and writes an opportunity brief: a 3-paragraph analysis covering the underlying client need, the right timing to revisit, how to re-open the conversation naturally, and what a compelling proposal would focus on.

The DeferredItem now appears in the Someday/Maybe list (Goals & Projects > Someday / Maybe) with status "scheduled" because it has a revisit date. On March 31, the system will automatically change the status to "in-review" and send Brian a notification. During his weekly reviews before then, the item will surface if it has not been reviewed in 30 days.

On the dashboard, the OpportunityPipelineWidget now includes this $8,000 item in the weighted pipeline value calculation.

## Steps

1. Open the task "Build analytics dashboard integration" for editing
2. Change status to "deferred"
3. Fill in deferral reason, note, revisit date, and trigger
4. Save the task
5. DeferralService creates the DeferredItem record
6. AI opportunity analysis runs asynchronously
7. Verify the item appears in the Someday/Maybe list
8. Check the OpportunityPipelineWidget reflects the new value

## System Features Used

- **Resources:** TaskResource (deferral fields), DeferredItemResource
- **Services:** DeferralService (deferTask), AiService (analyzeOpportunity)
- **Jobs:** AnalyzeDeferredOpportunity
- **Widgets:** OpportunityPipelineWidget
- **Models:** Task, DeferredItem, OpportunityPipeline

## Expected Outcome

The task is deferred with full context preserved -- the client's exact words, the reason, and a scheduled revisit date. A structured DeferredItem exists in the pipeline with an AI-generated opportunity brief. The system will automatically resurface this item at the right time, ensuring the $8,000 opportunity is never lost.
