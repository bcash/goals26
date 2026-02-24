# Resource Signal Tracking

**As a** Solas Run user, **I want** to capture resource signals from meeting notes -- mentions of budget changes, new technology needs, or team capacity shifts -- and link them to deferred items, **so that** I know exactly when a previously impossible opportunity becomes feasible.

## Scenario

During the Acme Corp check-in, several resource signals emerged in the conversation. The MeetingIntelligenceService extracted them automatically from the transcript, but Brian wants to review and enrich them.

He opens the Acme meeting record and scrolls to the ResourceSignalsRelationManager. Three signals were extracted:

1. **Budget signal:** "Our Q2 budget has been approved with a 30% increase over Q1." Constraint timeline: "April 2026." This signal connects directly to the deferred "Mobile app companion" item -- the client previously said the mobile app needed to wait for budget.

2. **Technology signal:** "We're migrating to a new e-commerce platform in April." Constraint timeline: "April-May 2026." This is relevant because the deferred "Advanced analytics dashboard" item depends on having the new platform in place.

3. **Team signal:** "We just hired a full-time content manager who starts in March." Constraint timeline: "March 2026." This means the deferred "Content strategy retainer" opportunity becomes viable -- the client now has someone to work with on ongoing content.

Brian clicks "Edit" on the budget signal and links it to the deferred mobile app item using the `deferred_item_id` field. He confirms that `creates_revisit_opportunity` is true. He does the same for the technology signal, linking it to the analytics dashboard deferred item.

For the team signal, Brian realizes this should trigger an immediate revisit of the content retainer opportunity. He navigates to Someday/Maybe, finds the "Content strategy retainer," and clicks "Review." He changes the outcome to "promote" to move it to the Opportunity Pipeline, sets the next action to "Schedule discovery call about content retainer," and notes: "Client hired a content manager. The retainer conversation is now viable."

The OpportunityPipelineService creates a new pipeline record for the content retainer at the "identified" stage with the estimated value of $2,400/month.

Back on the dashboard, the OpportunityPipelineWidget shows the new pipeline item and the updated weighted value. The "Actions Due This Week" section now lists: "Schedule discovery call about content retainer -- Acme Corp."

During the weekly review, the AI Someday/Maybe scan cross-references resource signals with deferred items and recommends: "The Acme Q2 budget increase ($30% more) aligns with two deferred items worth a combined $20,000. Consider scheduling a comprehensive proposal meeting in April."

## Steps

1. Open a meeting record and review extracted resource signals
2. Link resource signals to relevant deferred items
3. Identify signals that trigger immediate opportunity revisit
4. Promote a deferred item to the Opportunity Pipeline
5. Set next actions based on the resource signal timeline
6. Review cross-referenced signals during the weekly review
7. Use AI recommendations to time proposal conversations

## System Features Used

- **Resources:** ClientMeetingResource (ResourceSignalsRelationManager), DeferredItemResource, OpportunityPipelineResource
- **Services:** MeetingIntelligenceService (signal extraction), DeferralService (submitReview), OpportunityPipelineService
- **Widgets:** OpportunityPipelineWidget
- **Models:** MeetingResourceSignal, DeferredItem, OpportunityPipeline, ClientMeeting
- **AI Integration:** Weekly Someday/Maybe scan with resource signal cross-referencing

## Expected Outcome

Resource signals from meetings are captured, linked to relevant deferred items, and used to time the revisit of opportunities. When a client's budget increases, team changes, or technology shifts, the system connects these signals to the right deferred items, ensuring that Brian acts on opportunities at the exact moment they become feasible. The pipeline reflects reality-based timing rather than arbitrary follow-up schedules.
