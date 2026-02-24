# Post-Meeting Intelligence Extraction

**As a** Solas Run user, **I want** to import a meeting transcript and have the system automatically extract done items, scope decisions, action items, and resource signals, **so that** every meeting generates structured intelligence without manual data entry.

## Scenario

Brian just finished the Acme Corp check-in meeting. During the meeting, Granola was running and captured the full transcript with speaker diarization. Brian opens Goals & Projects > Client Meetings and sees the meeting list. He clicks the "Sync from Granola" header action button.

The GranolaSyncService runs `syncRecent()`, which calls `searchMeetings()` on the GranolaMcpClient. It finds the Acme meeting from today that has not been imported yet. The service calls `downloadNote()` and `downloadTranscript()` to fetch the structured notes and full transcript, then creates a ClientMeeting record linked to the Acme project with `source: granola` and `transcription_status: processing`.

The MeetingIntelligenceService `analyze()` method fires. It sends the transcript to the AI with a comprehensive extraction prompt. The AI returns a structured JSON response containing:

- **Summary:** A 3-sentence overview of the meeting discussion and key outcomes.
- **Done items:** Two items -- "Wireframes for all 5 pages approved" (with the client quote "These look great, exactly what we envisioned") and "Color palette finalized" (outcome: "Brand consistency established across all pages").
- **Action items:** Three tasks -- "Build homepage frontend" (high priority, due next Friday), "Send revised timeline to client" (medium, due tomorrow), and "Set up staging server" (medium, due next week).
- **Scope items:** Two in-scope ("Responsive design for tablet and mobile"), one out-of-scope ("Native mobile app -- confirmed not in this phase"), one assumption ("Client will provide final copy by next week").
- **Deferred items:** One -- "Mobile app companion" with reason "budget," opportunity type "phase-2," client quote "Love the idea but it needs to be next quarter's budget," estimated value $12,000.
- **Resource signals:** One budget signal -- "Client mentioned their Q2 budget opens in April" with constraint timeline "April 2026."

The service persists all extracted items: MeetingDoneItems are created, tasks are generated from action items, MeetingScopeItems are created, a DeferredItem is created for the mobile app, and a MeetingResourceSignal is stored. A Filament notification appears: "Meeting analyzed: Acme Corp Bi-Weekly Check-in -- 3 action items, 2 done items, 1 deferred item."

Brian opens the meeting record and sees three tabs: Meeting Details, Transcript, and Scope & Actions. The Scope & Actions tab shows the AI scope analysis. Below the form, the DoneItemsRelationManager and ResourceSignalsRelationManager display the extracted data.

## Steps

1. Click "Sync from Granola" on the Client Meetings list page
2. System imports the meeting via GranolaSyncService
3. MeetingIntelligenceService analyzes the transcript
4. Review the notification with extraction summary
5. Open the meeting record and browse the three tabs
6. Review extracted done items in the DoneItemsRelationManager
7. Review resource signals in the ResourceSignalsRelationManager
8. Check that new tasks were created from action items
9. Verify the deferred item was added to the Someday/Maybe list

## System Features Used

- **Resources:** ClientMeetingResource (tabbed form), DoneItemsRelationManager, ResourceSignalsRelationManager, ScopeItemsRelationManager
- **Services:** GranolaSyncService, GranolaMcpClient, MeetingIntelligenceService, DeferralService, AiService
- **Models:** ClientMeeting, MeetingDoneItem, MeetingResourceSignal, MeetingScopeItem, DeferredItem, Task
- **Jobs:** AnalyzeMeetingTranscript

## Expected Outcome

A single meeting transcript is transformed into structured intelligence: done items with client quotes, scope decisions, action tasks, deferred opportunities, and resource signals. No manual data entry is required beyond clicking the sync button. The deferred mobile app item is now in the pipeline with a $12,000 estimated value and a Q2 revisit trigger.
