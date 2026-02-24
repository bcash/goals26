# SOLAS RÚN
### *Document Amendments & Change Log*
**v1.2 — Granola MCP Integration (replaces webhook-based transcription)**

---

## Overview

This document records all changes made to existing Solas Rún documentation as a result of the following decision:

1. **Granola MCP as primary meeting integration** — replaces the multi-provider webhook-based transcription pipeline (Fireflies, AssemblyAI, Rev.ai, Whisper) with on-demand queries via Granola's MCP (Model Context Protocol) interface.

### Why This Change

The v1.1 architecture required managing webhook endpoints, signed URLs, multiple API credentials, a `TranscriptionIngestionService`, and background jobs to ingest transcripts from various providers. This was complex plumbing for what is fundamentally a "get me the meeting data" operation.

Granola (granola.ai) is now the primary meeting notepad. It runs during meetings, captures audio, and generates structured AI notes. Its MCP interface exposes three tools — `search_meetings`, `download_note`, and `download_transcript` — which Solas Rún can call on demand. This is a **pull model** instead of a push model: simpler, fewer moving parts, no webhook infrastructure.

---

## Table of Contents

1. [Meeting Intelligence — solas-run-meeting-intelligence.md](#1-meeting-intelligence)
2. [Laravel Setup — solas-run-laravel-setup.md](#2-laravel-setup)
3. [Blueprint — solas-run-blueprint.md](#3-blueprint)

---

## 1. Meeting Intelligence

**File:** `solas-run-meeting-intelligence.md`
**Version:** v1.0 → v1.2

### Header

Updated version to v1.2 and footer to match.

### Overview bullets

**Changed:** "Transcription — automatic ingestion via API from transcription services"
**To:** "Granola MCP Integration — on-demand access to meeting notes, transcripts, and calendar events via Granola's MCP interface"

### Table of Contents

**Changed:** "Transcription API Integration" → "Granola MCP Integration"

### Section 2 — Complete Replacement

**Removed entirely:**
- Supported Transcription Sources table (Otter.ai, Fireflies, AssemblyAI, Rev.ai, Whisper, Custom upload)
- Webhook-based Transcription Flow diagram
- `TranscriptionWebhookController` (all three methods: fireflies, assemblyai, manual)
- API routes for webhook endpoints
- `TranscriptionIngestionService` (full class)
- User Webhook Tokens section

**Replaced with:**
- "Why Granola?" explanation
- "What Granola MCP Provides" table (search_meetings, download_note, download_transcript)
- New Integration Architecture flow diagram (pull-based)
- `GranolaMcpClient` service class (searchMeetings, downloadNote, downloadTranscript)
- `GranolaSyncService` class (syncRecent, importMeeting, resyncMeeting)
- Optional scheduled sync via Laravel Scheduler
- Manual sync from Filament (header action on ClientMeeting list)
- Environment Configuration (.env and config/services.php)
- Instructions for running the Granola MCP server locally

### Section 5 — Updated Tables → `client_meetings`

**Changed columns:**
- `transcription_source` → `source` (values: `granola | manual`)
- `transcription_external_id` → `granola_meeting_id` (unique, used for sync)

### Section 7 — Models → ClientMeeting

**Updated fillable:**
- `transcription_source` → `source`
- `transcription_external_id` → `granola_meeting_id`

### Section 10 — Filament Resources

**Changed:** Transcript tab helper text
- From: "Paste transcript here, or connect a transcription service via Settings."
- To: "Synced from Granola automatically, or paste a transcript manually."

### Section 11 — AI Integration Points

**Changed:** Full Transcript Analysis trigger
- From: "Transcript received via webhook or manual upload"
- To: "Meeting synced from Granola (automatic or manual sync) or transcript pasted manually"

---

## 2. Laravel Setup

**File:** `solas-run-laravel-setup.md`
**Version:** v1.1 → v1.2

### Transcription API Clients section

**Removed entirely:**
- Fireflies, AssemblyAI, Rev, OpenAI .env keys
- `config/services.php` entries for assemblyai and fireflies

**Replaced with:**
- Granola MCP .env keys (`GRANOLA_MCP_URL`, `GRANOLA_MCP_TRANSPORT`)
- `config/services.php` entry for granola
- Instructions for running the Granola MCP server

### Directory & File Structure

**Removed:**
- `TranscriptionWebhookController.php` from Http/Controllers
- `TranscriptionIngestionService.php` from Services
- `ProcessTranscriptionWebhook.py` from Jobs

**Added:**
- `GranolaMcpClient.php` to Services
- `GranolaSyncService.php` to Services

---

## 3. Blueprint

**File:** `solas-run-blueprint.md`
**Version:** v1.1 → v1.2

### Section III — Data Model → `client_meetings` entity

**Changed:** Entity description
- From: references Fireflies, AssemblyAI, Rev.ai, Whisper, manual upload
- To: references Granola MCP interface, no webhooks or transcript storage required

### Section VII — Build Phases → Phase 2

**Changed:** "Meeting transcription webhook endpoints" → "Granola MCP integration — sync meetings on demand"

---

## Documents NOT Changed in v1.2

The following documents were not affected by the Granola MCP migration:

- `solas-run-multitenancy.md` — no changes (tenancy pattern unaffected)
- `solas-run-filament-resources.md` — no changes (resource definitions unaffected)
- `solas-run-dashboard-widgets.md` — no changes (widgets unaffected)
- `solas-run-task-decomposition.md` — no changes (task tree unaffected)
- `solas-run-deferral-pipeline.md` — no changes (deferral logic unaffected)
- `solas-run-amendments-v1.1.md` — preserved as historical record

---

*Solas Rún • Version 1.2 • Amendments & Change Log*
