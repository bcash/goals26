# Corporate Tax Management with TaxAct

**As a** Solas Run user who owns multiple businesses, **I want** to manage the entire process of doing corporate taxes for all of my business entities and personal taxes using TaxAct, **so that** I can orchestrate a complex, multi-entity tax season from document gathering through filing without missing deadlines, losing documents, or forgetting any entity.

## Scenario

It is late January 2026 and tax season is approaching. Brian owns three business entities -- Acme LLC (a consulting firm), Ember Creative LLC (a production company), and a 25% partnership stake in Signal Ventures -- plus his personal taxes. All filing goes through TaxAct. He decides to build the entire tax process in Solas Run.

Brian starts by navigating to Goals & Projects > Goals and creating a new goal: "Complete Tax Season 2026 -- All Entities Filed" under the Finance Life Area. He sets the horizon to "90-day," the target date to April 15, 2026, and writes the "why" statement: "Because getting taxes done early eliminates months of background anxiety and avoids penalties. This is the system working for me, not against me." He sets progress to 0%.

Next, he creates a project: "Tax Season 2026" under the Finance Life Area, linked to this goal, with "TaxAct" noted in the description as the filing service. No external client -- this is self-work.

Brian then builds a hierarchical task tree using the Task Tree page. The root task is "File All 2026 Tax Returns." Under it, he creates four major branches -- one for each entity and one for personal:

**Branch 1: Acme LLC (S-Corp)**
- Gather Documents
  - Download all bank statements (Jan-Dec)
  - Export QuickBooks profit & loss report
  - Collect 1099-NEC forms from all clients
  - Compile business expense receipts
  - Get health insurance premium statements
  - Obtain retirement plan contribution records
- Organize & Categorize
  - Categorize all expenses by Schedule C line items
  - Reconcile QuickBooks with bank statements
  - Calculate home office deduction percentage
- Enter into TaxAct
  - Create S-Corp return in TaxAct
  - Enter revenue and income figures
  - Enter all deductions and expenses
  - Calculate officer compensation (W-2)
  - Generate K-1 for personal return
- Review & File
  - Review return for accuracy
  - Compare to prior year for anomalies
  - File S-Corp return electronically

**Branch 2: Ember Creative LLC (Single-Member)**
- Similar structure for the production company

**Branch 3: Signal Ventures Partnership (K-1 Recipient)**
- Wait for K-1 from partnership
- Review K-1 for accuracy
- Enter partnership income in personal return

**Branch 4: Personal Return**
- Gather personal documents (W-2s from entities, mortgage interest, charitable donations, investment statements)
- Wait for all K-1s to arrive
- Enter all income sources in TaxAct
- Enter deductions and credits
- Review and file

Brian creates milestone checkpoints for each entity: "Acme LLC S-Corp return filed" (due March 15 -- S-Corp deadline), "Ember Creative LLC return filed" (due March 15), "Signal Ventures K-1 received" (due March 31), and "Personal return filed" (due April 15). Each milestone is linked to the goal and has a specific due date.

For document tracking, Brian creates deferred items for things he is waiting on. He defers "Receive K-1 from Signal Ventures partnership" with reason "awaiting-decision" and revisit date March 15. He defers "Follow up with accountant on depreciation schedule" with reason "awaiting-decision" and trigger "After accountant returns from vacation Feb 10." Each deferred item is linked to the Tax Season project.

Brian schedules dedicated time for tax work. He creates recurring time blocks: "Tax prep session" on Saturday mornings from 9 AM to 12 PM, block type "deep-work," linked to the Tax Season project. Three hours of focused tax work each weekend keeps the process moving without overwhelming weekday schedules.

He uses the meeting agenda feature to prepare for his accountant meeting. He creates a meeting agenda: "Tax Strategy Review with CPA" with client type "external," scheduled for February 20. The AgendaService auto-populates action follow-ups from open tax tasks and adds the deferred depreciation question as a "deferred-review" agenda item. Brian adds custom topics: "S-Corp officer compensation strategy," "Estimated tax payments review," and "New deductions for home office expansion."

After the accountant meeting, Brian syncs the transcript from Granola. The MeetingIntelligenceService extracts action items ("Update depreciation schedule to include new equipment," "Increase Q1 estimated payment by $500"), scope decisions ("Home office deduction confirmed at 15% of square footage"), and a resource signal ("CPA recommended switching to accrual accounting next year -- technology change needed").

Brian logs time entries for his tax prep sessions. Each Saturday, he creates a time entry: project "Tax Season 2026," linked to whichever branch he worked on, 3 hours, description of what he accomplished. These entries are not billable (personal tax work) but track his investment of time. Over the course of tax season, he logs 28 hours of tax prep work -- valuable data for planning next year.

Throughout February and March, Brian monitors progress on the dashboard. The GoalProgressWidget shows "Complete Tax Season 2026" with a gold progress bar that advances as tasks are completed. When he finishes all Acme LLC tasks and files the S-Corp return, a quality gate triggers on the "Acme LLC" branch. The AI-generated checklist asks: "Has officer compensation been correctly reported on W-2?", "Were all 1099-NEC forms accounted for?", "Does the K-1 match the S-Corp net income?", and "Was the return filed before the March 15 deadline?" Brian passes the gate, and the "Acme LLC S-Corp return filed" milestone is marked complete.

Brian uses journal entries to capture tax decisions and learnings. He writes entries tagged "taxes," "finance," "TaxAct" documenting decisions like "Chose to take the standard deduction for Ember Creative because actual expenses were lower this year" and "Need to increase estimated payments next year -- owed $1,200 in underpayment."

When the Signal Ventures K-1 finally arrives on March 20, the deferred item surfaces in the weekly review. Brian marks it as resolved, enters the K-1 data into his personal return, and completes the last branch of the tree. The final quality gate on "File All 2026 Tax Returns" triggers with questions like: "Have all entity returns been filed?", "Are all K-1s reflected in the personal return?", "Have estimated payments for next year been calculated?", and "Are all supporting documents organized for potential audit?"

Brian passes the gate on April 5 -- ten days before the deadline. The goal progress hits 100%. He writes a final journal entry: "Tax Season 2026 complete. 28 hours invested over 10 weekends. Key learning: start gathering documents in January, not February. The deferred item tracking for K-1 arrivals worked perfectly -- no scrambling at the last minute."

## Steps

1. Create a "Complete Tax Season 2026" goal under Finance Life Area
2. Create a "Tax Season 2026" project noting TaxAct as the filing service
3. Build a hierarchical task tree with branches per entity
4. Create milestones with entity-specific filing deadlines
5. Create deferred items for documents and information awaited
6. Schedule recurring "Tax prep session" time blocks on weekends
7. Build a meeting agenda for the CPA meeting using AgendaService
8. Sync the CPA meeting from Granola and review extracted intelligence
9. Log time entries for each tax prep session
10. Monitor progress via the GoalProgressWidget on the dashboard
11. Pass quality gates as each entity's return is completed
12. Write journal entries documenting tax decisions and learnings
13. Process deferred items (K-1 arrivals) as they resolve
14. Pass the final quality gate when all returns are filed

## System Features Used

- **Resources:** GoalResource, ProjectResource, TaskResource, MilestoneResource, DeferredItemResource, TimeEntryResource, MeetingAgendaResource, ClientMeetingResource, JournalEntryResource, DailyPlanResource (TimeBlocksRelationManager)
- **Widgets:** GoalProgressWidget, TimeBlockTimelineWidget, MorningChecklistWidget, OpportunityPipelineWidget
- **Services:** TaskTreeService, DecompositionInterviewService, QualityGateService, AgendaService, DeferralService, BudgetService (time tracking), MeetingIntelligenceService, GranolaSyncService, AiService
- **Models:** Goal, Project, Task (hierarchical tree), Milestone, DeferredItem, TimeEntry, TimeBlock, MeetingAgenda, AgendaItem, ClientMeeting, JournalEntry, TaskQualityGate
- **AI Integration:** Quality gate checklist generation, meeting transcript analysis, agenda topic suggestions
- **Pages:** TaskTree, DecompositionInterview, QualityGateReview

## Expected Outcome

A complex, multi-entity tax season is managed end-to-end within Solas Run. Every document, every entity, every deadline, and every accountant interaction is tracked in a structured system. Deferred items prevent forgotten K-1s and follow-ups. Quality gates ensure each return is reviewed before filing. Time blocks protect dedicated tax prep sessions. Journal entries create a knowledge base for next year's tax season. The 28 hours of tax prep work are logged for future planning. Brian files all returns ten days early with zero last-minute scrambling, and the entire process is documented for replication next year.
