# Weekly Review Process

**As a** Solas Run user, **I want** to conduct a structured weekly review that scores each Life Area, reflects on wins and friction, reviews deferred items, and sets next week's focus, **so that** I maintain a regular rhythm of self-assessment and strategic adjustment.

## Scenario

It is Sunday evening at 7 PM. Brian opens Solas Run and navigates to Journal > Weekly Reviews, then clicks "Create." The form auto-fills the week start date to the previous Monday.

In the Wins section, Brian writes about shipping the decomposition feature, completing his Spanish A1 milestone, and maintaining a 23-day meditation streak. In the Friction section, he notes that email interruptions broke his focus twice, a client meeting ran over and ate into his writing block, and he skipped his walk twice due to rain.

He moves to the Life Area Scores section. The form shows all six areas with dropdown selectors from 1-5. He scores Creative at 4 (good writing progress), Business at 5 (feature shipped, client happy), Health at 3 (walked only 3 of 5 days), Family at 4 (quality time on Saturday), Growth at 4 (Spanish milestone hit), and Finance at 3 (need to review invoice pipeline). He sets the overall score to 4 out of 5.

In the Next Week Focus field, Brian writes: "Protect afternoon writing blocks -- no meetings after 1 PM. Follow up on three unpaid invoices. Start the Acme Corp wireframes."

After saving, the AI Analysis section populates with a generated weekly analysis. The AiService receives the week's data -- daily plan ratings, habit completion rates, goal progress changes, and the review content -- and produces a summary that highlights patterns: "Your energy and focus were highest on days with morning meditation completion. Consider moving your deep work blocks earlier to capitalize on morning energy."

Before closing the review, Brian checks the deferred items surfaced by the DeferralService. The weekly review integration shows three items overdue for review and two high-value someday items. He clicks "Review" on each, making quick decisions: one gets rescheduled to next month, one gets promoted to the opportunity pipeline, and one gets archived. The AI runs a Someday/Maybe scan and recommends revisiting a deferred client proposal worth $8,000 because the client's Q1 budget just opened.

## Steps

1. Navigate to Journal > Weekly Reviews and click "Create"
2. Write wins and friction for the week
3. Score each Life Area from 1-5
4. Set the overall week score
5. Write next week's focus statement
6. Save and review the AI-generated analysis
7. Process overdue deferred items surfaced by the DeferralService
8. Review the AI Someday/Maybe scan recommendations

## System Features Used

- **Resources:** WeeklyReviewResource, DeferredItemResource
- **Services:** AiService (weekly analysis), DeferralService (getWeeklyReviewItems), OpportunityPipelineService
- **Models:** WeeklyReview, DeferredItem, DeferralReview
- **AI Integration:** Weekly analysis prompt, Someday/Maybe scan

## Expected Outcome

A complete weekly review record exists with Life Area scores, reflection, and AI analysis. Deferred items have been triaged. Brian has a clear focus for the coming week, patterns have been identified by AI, and the Someday/Maybe list is actively maintained rather than stagnating.
