# Opportunity Pipeline Management

**As a** Solas Run user, **I want** to review deferred items that became opportunities, advance them through pipeline stages, and track the weighted pipeline value, **so that** I can forecast future revenue and act on the right opportunities at the right time.

## Scenario

Brian has accumulated seven deferred items with commercial value over the past three months. During his monthly Opportunity Review, he opens Goals & Projects > Opportunity Pipeline to see the full pipeline view.

The table shows his opportunities sorted by next action date. He sees:
- "Mobile app companion - Acme Corp" at Qualifying stage, $12,000, 30% probability
- "Analytics dashboard - Acme Corp" at Identified stage, $8,000, 20% probability
- "E-commerce integration - Beta LLC" at Nurturing stage, $15,000, 40% probability
- "Content strategy retainer - Acme Corp" at Proposing stage, $2,400/mo, 60% probability

The dashboard's OpportunityPipelineWidget shows the weighted pipeline total: $12,480 (sum of each opportunity's estimated value multiplied by probability). Below that, it lists two actions due this week: "Follow up with Beta LLC about e-commerce timeline" and "Send retainer proposal to Acme."

Brian clicks the "Advance Stage" action on the Acme retainer opportunity. The OpportunityPipelineService moves it from "Proposing" to "Negotiating" and Brian updates the probability to 75%. The weighted value recalculates.

For the Analytics dashboard item, which was just identified, Brian clicks "Edit" and reviews the AI opportunity analysis. He sets the next action to "Schedule call with Acme data team lead" with a date two weeks out. He bumps the probability to 25%.

One opportunity -- "Brand refresh for Gamma Inc" -- has been in the Nurturing stage for 90 days with no movement. Brian decides to close it as lost, entering the reason: "Client went with an internal designer." The DeferredItem status updates to "lost."

For the mobile app opportunity, the resource signal captured from the meeting indicated the client's Q2 budget opens in April. Brian sets the next action date to April 1 and writes: "Re-open mobile app conversation when Q2 budget is confirmed."

After reviewing all items, the pipeline summary shows: 5 active opportunities worth $41,400 total, $16,800 weighted. Brian notes this in a journal entry for his records.

## Steps

1. Navigate to Goals & Projects > Opportunity Pipeline
2. Review the pipeline table sorted by next action date
3. Click "Advance Stage" on the retainer opportunity
4. Update probability and save
5. Edit the analytics dashboard opportunity and set next action
6. Close the Gamma Inc opportunity as lost with a reason
7. Set revisit timing on the mobile app opportunity based on resource signals
8. Review the OpportunityPipelineWidget on the dashboard

## System Features Used

- **Resources:** OpportunityPipelineResource, DeferredItemResource
- **Services:** OpportunityPipelineService (advanceStage, closeWon, getSummary, totalWeightedPipeline)
- **Widgets:** OpportunityPipelineWidget
- **Models:** OpportunityPipeline, DeferredItem, MeetingResourceSignal

## Expected Outcome

The pipeline is actively managed with opportunities at various stages. Weighted pipeline value is tracked and displayed on the dashboard. Stale opportunities are closed with documented reasons. Next actions are scheduled based on resource signals from meetings, ensuring Brian follows up at exactly the right time when client budgets and readiness align.
