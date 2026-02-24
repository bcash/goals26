# Granola Meeting Sync

**As a** Solas Run user, **I want** to sync meetings from Granola MCP so that transcripts are imported and AI analysis runs automatically, **so that** my meeting notes become structured intelligence without manual transcription or data entry.

## Scenario

Brian had three meetings today: a discovery call with a potential new client, a check-in with Acme Corp, and an internal planning session with himself about Q2 goals. All three were captured by Granola, which was running during each meeting.

At 5 PM, Brian opens Goals & Projects > Client Meetings. The list shows his existing meeting records. He clicks the "Sync from Granola" header action button.

The GranolaSyncService `syncRecent()` method fires. It calls the GranolaMcpClient's `searchMeetings()` method, which queries the Granola MCP server at `http://localhost:3333`. The response contains metadata for today's three meetings: titles, dates, attendees, and Granola meeting IDs.

The service checks each meeting against the database using `granola_meeting_id`. None of the three have been imported yet, so it proceeds with all three.

For each meeting, the service calls `downloadNote()` to get the AI-generated structured notes (sections/panels with metadata) and `downloadTranscript()` to get the full transcript with speaker diarization. It creates a ClientMeeting record for each:

1. **Discovery call with Delta Corp** -- `client_type: external`, `meeting_type: discovery`, linked to no project yet, source: granola
2. **Acme Corp bi-weekly check-in** -- `client_type: external`, `meeting_type: check-in`, linked to the Acme project, source: granola
3. **Q2 Goal Planning Session** -- `client_type: self`, `meeting_type: planning`, linked to no project, source: granola

After creating each record, the MeetingIntelligenceService `analyze()` method runs. For the Acme meeting, it extracts two done items, one deferred item, and three action items. For the discovery call, it extracts scope items and resource signals from the potential client's needs. For the self-meeting, it extracts personal goal decisions and deferred ambitions.

A Filament notification appears: "3 meeting(s) synced from Granola." Each meeting has its own analysis notification showing the extraction summary.

Brian opens the Delta Corp discovery meeting and sees the three-tab form: Meeting Details shows the metadata, Transcript shows the full imported notes, and Scope & Actions shows the AI scope analysis. The relation managers below display the extracted done items, scope items, and resource signals.

If Granola updates its notes for a meeting (for example, if Brian edited them in Granola), he can click "Resync" on an individual meeting to pull fresh data via `resyncMeeting()`.

## Steps

1. Complete meetings during the day with Granola running
2. Navigate to Client Meetings list page
3. Click "Sync from Granola" header action
4. System imports all new meetings via GranolaSyncService
5. MeetingIntelligenceService analyzes each transcript
6. Review the notification showing sync results
7. Open each meeting to review extracted intelligence
8. Optionally resync a meeting if notes were updated in Granola

## System Features Used

- **Resources:** ClientMeetingResource (with sync action)
- **Services:** GranolaSyncService (syncRecent, importMeeting, resyncMeeting), GranolaMcpClient (searchMeetings, downloadNote, downloadTranscript), MeetingIntelligenceService (analyze)
- **Models:** ClientMeeting, MeetingDoneItem, MeetingResourceSignal, MeetingScopeItem, DeferredItem, Task
- **Config:** services.granola (mcp_url, transport)

## Expected Outcome

Three meetings are imported from Granola with full transcripts and AI-structured notes. Each meeting is analyzed and produces structured intelligence: done items, scope decisions, action items, deferred opportunities, and resource signals. Brian has complete meeting records in Solas Run without any manual transcription, and each meeting's intelligence feeds into his task tree, deferral pipeline, and project tracking.
