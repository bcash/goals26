# Quality Gate Review

**As a** Solas Run user, **I want** to review an AI-generated quality checklist before marking a parent task as complete, **so that** I catch scope gaps and quality issues before declaring work done, preventing false progress.

## Scenario

Brian has been working on the "Design phase" task for the Acme Corp project. All three children are now marked done: "Wireframes" (approved by client), "Visual mockups" (all five pages finalized), and "Design review meeting" (held with stakeholders). The TaskTreeService's `propagateUpward()` method detected that all siblings are complete and triggered the QualityGateService.

A persistent Filament notification appears: "Quality gate ready: Design phase -- All subtasks complete. Review required before marking done." The notification includes a "Review Now" action button that links to the Quality Gate Review page.

Brian clicks "Review Now" and lands on the review page. The QualityGateService had called `generateChecklist()` when the gate was triggered, and the AI generated five context-aware review questions:

1. "Have all wireframes been formally approved by the Acme Corp stakeholders?"
2. "Do the visual mockups cover all five pages including responsive breakpoints?"
3. "Were all feedback items from the design review meeting addressed?"
4. "Has the design been checked against Acme's brand guidelines?"
5. "Are the design assets exported and organized for the development team?"

For each question, Brian sees a text field for his answer and a pass/fail toggle. He works through them:

- Question 1: "Yes, approved in the March 15 check-in." -- PASS
- Question 2: "Yes, all five pages have desktop, tablet, and mobile mockups." -- PASS
- Question 3: "Three of four items addressed. The footer variation was deprioritized." -- PASS (with a note)
- Question 4: "Checked against brand guide v2.1. Logo usage follows specs." -- PASS
- Question 5: "Assets exported as Figma components. Dev handoff link shared." -- PASS

Brian adds reviewer notes: "Footer variation was consciously deprioritized by client -- captured as a deferred item for phase 2." He clicks "Pass Gate." The QualityGateService `submitReview()` method updates the gate to "passed," marks the "Design phase" task as "done," and propagates upward. Since the other sibling tasks under the root ("Development" and "QA & Launch") are not yet complete, the root's gate does not trigger.

If Brian had failed the gate, the children would have been reopened with `quality_gate_status: failed` for rework.

## Steps

1. Receive a quality gate notification when all children are complete
2. Click "Review Now" to open the Quality Gate Review page
3. Review each AI-generated checklist question
4. Answer each question and mark pass or fail
5. Add reviewer notes with any context
6. Click "Pass Gate" or "Fail Gate"
7. System updates the parent task status and propagates upward

## System Features Used

- **Pages:** QualityGateReview
- **Services:** QualityGateService (trigger, generateChecklist, submitReview), TaskTreeService (propagateUpward)
- **Models:** TaskQualityGate, Task
- **AI Integration:** Quality gate checklist generation
- **Notifications:** Persistent Filament notification with action link

## Expected Outcome

The "Design phase" parent task is marked as done only after a structured quality review. The AI-generated checklist ensured that brand guidelines, stakeholder approval, asset delivery, and feedback resolution were all verified. The review record is stored in the `task_quality_gates` table as an audit trail, and upward propagation continues through the tree.
