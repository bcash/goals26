# SOLAS RÚN
### *Meeting Intelligence*
**Technical Reference v1.2**

---

## Overview

A meeting is not an interruption to the work — it *is* the work. Every client conversation, every internal planning session, every brainstorm with yourself is a source of goals, decisions, deferred possibilities, risks, and actionable tasks. Solas Rún treats meetings as first-class intelligence events.

This document covers the full meeting lifecycle in Solas Rún:

- **Granola MCP Integration** — on-demand access to meeting notes, transcripts, and calendar events via Granola's MCP interface
- **Agenda Builder** — structured preparation for future meetings
- **Scope & Decision Extraction** — AI-powered parsing of what was said, decided, deferred, and excluded
- **Done Tracking** — capturing completed work discussed in meetings
- **Deferred Item Capture** — surfacing future opportunities from "not this time" moments
- **Self-as-Client** — treating your own internal planning sessions with the same rigor as client meetings

---

## Table of Contents

1. [Meeting Types & The Self-as-Client Model](#1-meeting-types--the-self-as-client-model)
2. [Granola MCP Integration](#2-granola-mcp-integration)
3. [Agenda Builder](#3-agenda-builder)
4. [Meeting Intelligence Extraction](#4-meeting-intelligence-extraction)
5. [New & Updated Tables](#5-new--updated-tables)
6. [Migrations](#6-migrations)
7. [Models](#7-models)
8. [MeetingIntelligenceService](#8-meetingintelligenceservice)
9. [AgendaService](#9-agendaservice)
10. [Filament Resources](#10-filament-resources)
11. [AI Integration Points](#11-ai-integration-points)

---

## 1. Meeting Types & The Self-as-Client Model

### You Are Your Own Most Important Client

In Solas Rún, the word "client" does not mean only an external paying customer. **You are a client.** Your goals, your life areas, your creative projects, your health ambitions — these all follow the same discipline as any professional engagement:

- They have a scope
- They have a budget (of time, money, energy, and capability)
- They have deliverables
- They have things that are in-scope, out-of-scope, and deferred
- They have a quality standard
- They can be over-promised and under-resourced just like any client project

When you sit down on a Sunday evening to plan your week, that is a **client meeting with yourself**. When you brainstorm a novel, that is a **discovery session**. When you review your health goals, that is a **check-in**. The system treats all of these with equal rigor.

### Meeting Types

| Type | External Use | Internal / Self Use |
|------|-------------|---------------------|
| `discovery` | First meeting with a new client | First brainstorm on a new life goal |
| `requirements` | Gathering detailed specs | Defining what a goal really means |
| `check-in` | Progress review with client | Weekly self-review of a goal |
| `brainstorm` | Creative session with client | Idea generation for any project |
| `review` | Client reviews a deliverable | Reviewing your own completed work |
| `planning` | Project planning session | Goal decomposition session |
| `retrospective` | End-of-project review | Personal after-action review |
| `handoff` | Delivering the completed work | Marking a life goal achieved |

### The Internal Meeting Pattern

When `client_type = 'self'`, the system adapts its language and prompts:

| External Concept | Internal Equivalent |
|-----------------|---------------------|
| Client name | "Me" / your name |
| Client budget | Available time, money, capability, energy |
| Client requirements | Personal goal definition |
| Scope creep | Taking on more than your current resources allow |
| Out-of-scope | Goals deferred until resources are ready |
| Proposal | A personal commitment plan |
| Invoice | Time and energy investment log |

---

## 2. Granola MCP Integration

### Why Granola?

Granola (granola.ai) is a smart meeting notepad that runs during your meetings, captures audio, and generates structured AI meeting notes. Instead of managing webhook endpoints and multiple transcription API credentials, Solas Rún queries Granola on demand via its **MCP (Model Context Protocol) interface**.

This is a pull model, not a push model. Your Laravel app asks Granola for meeting data when it needs it — no webhooks, no ingestion queue, no transcript storage.

### What Granola MCP Provides

| MCP Tool | What It Returns | Solas Rún Use |
|----------|----------------|---------------|
| `search_meetings` | Meeting list with titles, dates, attendees; supports keyword search, list filtering, and pagination | Find recent meetings, match to projects, populate sync queue |
| `download_note` | AI-generated structured meeting notes (panels/sections) with metadata (section count, bullet count, word count) | Primary source for intelligence extraction — notes are already structured |
| `download_transcript` | Full transcript with speaker diarization, segment count, duration, and speaker breakdown | Deep analysis when notes aren't sufficient, verbatim quote extraction |

### Integration Architecture

```
Granola (runs during meetings)
     │
     │  Meeting notes + transcripts stored in Granola
     │
     ▼
GranolaMcpClient (Laravel service)
     │  ├─ search_meetings() — find new/updated meetings
     │  ├─ download_note()   — fetch structured notes
     │  └─ download_transcript() — fetch full transcript
     │
     ▼
MeetingIntelligenceService::analyzeFromGranola()
     │  ├─ Match to existing ClientMeeting (or create new)
     │  ├─ Extract scope items
     │  ├─ Extract decisions
     │  ├─ Extract action items
     │  ├─ Extract deferred items
     │  ├─ Extract done items
     │  └─ Extract cost/resource signals
     │
     ▼
Notify user: "Meeting synced — X items found"
```

### GranolaMcpClient Service

```php
// app/Services/GranolaMcpClient.php

namespace App\Services;

use App\Models\ClientMeeting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GranolaMcpClient
{
    /**
     * Search for meetings in Granola.
     *
     * @param string|null $query   Keyword search (title, attendees)
     * @param string|null $listId  Filter by Granola list/folder
     * @param int         $limit   Max results to return
     * @param string|null $cursor  Pagination cursor from previous search
     */
    public function searchMeetings(
        ?string $query  = null,
        ?string $listId = null,
        int     $limit  = 25,
        ?string $cursor = null
    ): array {
        return $this->callMcpTool('search_meetings', array_filter([
            'query'   => $query,
            'list_id' => $listId,
            'limit'   => $limit,
            'cursor'  => $cursor,
        ]));
    }

    /**
     * Download the AI-generated structured notes for a meeting.
     *
     * Returns sections/panels with metadata:
     *   section_count, bullet_count, word_count
     */
    public function downloadNote(string $granolaMeetingId): array
    {
        return $this->callMcpTool('download_note', [
            'meeting_id' => $granolaMeetingId,
        ]);
    }

    /**
     * Download the full transcript with speaker diarization.
     *
     * Returns segments with:
     *   speaker, text, timestamp
     * Plus metadata: segment_count, duration, speaker_breakdown
     */
    public function downloadTranscript(string $granolaMeetingId): array
    {
        return $this->callMcpTool('download_transcript', [
            'meeting_id' => $granolaMeetingId,
        ]);
    }

    /**
     * Call a Granola MCP tool.
     *
     * The MCP transport layer depends on your setup:
     * - stdio (local process)
     * - HTTP/SSE (remote server)
     *
     * This implementation uses HTTP transport to a local MCP server.
     * Adjust the base URL and auth method to match your Granola MCP setup.
     */
    private function callMcpTool(string $tool, array $arguments): array
    {
        $response = Http::baseUrl(config('services.granola.mcp_url'))
            ->timeout(30)
            ->post('/call-tool', [
                'name'      => $tool,
                'arguments' => $arguments,
            ]);

        if ($response->failed()) {
            Log::error("Granola MCP call failed: {$tool}", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException("Granola MCP call failed: {$tool}");
        }

        return $response->json('content.0.text')
            ? json_decode($response->json('content.0.text'), true) ?? []
            : $response->json();
    }
}
```

### Meeting Sync Service

The sync service bridges Granola and the local database. It runs on demand (from Filament) or on a schedule.

```php
// app/Services/GranolaSyncService.php

namespace App\Services;

use App\Models\ClientMeeting;
use Carbon\Carbon;

class GranolaSyncService
{
    public function __construct(
        protected GranolaMcpClient $granola,
        protected MeetingIntelligenceService $intelligence
    ) {}

    /**
     * Sync recent meetings from Granola.
     * Finds meetings not yet imported, downloads notes + transcript,
     * and runs intelligence extraction.
     */
    public function syncRecent(?string $query = null, int $limit = 10): array
    {
        $results   = $this->granola->searchMeetings(query: $query, limit: $limit);
        $meetings  = $results['meetings'] ?? $results;
        $synced    = [];

        foreach ($meetings as $gMeeting) {
            $granId = $gMeeting['id'] ?? $gMeeting['meeting_id'] ?? null;
            if (!$granId) continue;

            // Skip if already synced
            if (ClientMeeting::where('granola_meeting_id', $granId)->exists()) {
                continue;
            }

            $synced[] = $this->importMeeting($granId, $gMeeting);
        }

        return $synced;
    }

    /**
     * Import a single meeting from Granola by its ID.
     */
    public function importMeeting(string $granolaMeetingId, array $meta = []): ClientMeeting
    {
        // Fetch structured notes and transcript in parallel
        $note       = $this->granola->downloadNote($granolaMeetingId);
        $transcript = $this->granola->downloadTranscript($granolaMeetingId);

        // Create or update local meeting record
        $meeting = ClientMeeting::updateOrCreate(
            ['granola_meeting_id' => $granolaMeetingId],
            [
                'user_id'       => auth()->id(),
                'title'         => $meta['title'] ?? $note['title'] ?? 'Untitled Meeting',
                'meeting_date'  => Carbon::parse($meta['date'] ?? $note['date'] ?? now()),
                'meeting_type'  => 'check-in',
                'client_type'   => 'external',
                'attendees'     => $meta['attendees'] ?? $note['attendees'] ?? [],
                'source'        => 'granola',
                'transcript'    => $this->formatTranscript($transcript),
                'summary'       => $this->formatNotes($note),
                'transcription_status'   => 'received',
                'transcript_received_at' => now(),
            ]
        );

        // Run AI intelligence extraction
        $this->intelligence->analyze($meeting);

        return $meeting;
    }

    /**
     * Re-sync an existing meeting (e.g. notes were updated in Granola).
     */
    public function resyncMeeting(ClientMeeting $meeting): ClientMeeting
    {
        if (!$meeting->granola_meeting_id) {
            throw new \InvalidArgumentException('Meeting has no Granola ID — cannot resync.');
        }

        return $this->importMeeting($meeting->granola_meeting_id, [
            'title'     => $meeting->title,
            'date'      => $meeting->meeting_date->toIso8601String(),
            'attendees' => $meeting->attendees,
        ]);
    }

    private function formatTranscript(array $transcript): string
    {
        $segments = $transcript['segments'] ?? $transcript;

        return collect($segments)
            ->map(fn ($seg) => "[{$seg['speaker']}] {$seg['text']}")
            ->join("\n");
    }

    private function formatNotes(array $note): string
    {
        $panels = $note['panels'] ?? $note['sections'] ?? [];

        return collect($panels)
            ->map(fn ($panel) => "## {$panel['title']}\n{$panel['content']}")
            ->join("\n\n");
    }
}
```

### Scheduled Sync (Optional)

```php
// app/Console/Kernel.php (or routes/console.php in Laravel 11)

use App\Services\GranolaSyncService;

Schedule::call(function () {
    app(GranolaSyncService::class)->syncRecent(limit: 10);
})->hourly()->name('granola-sync')->withoutOverlapping();
```

### Manual Sync from Filament

A Filament action on the ClientMeeting list page allows one-click sync:

```php
// In ClientMeetingResource ListRecords page

use Filament\Actions\Action;

protected function getHeaderActions(): array
{
    return [
        Action::make('syncGranola')
            ->label('🔄 Sync from Granola')
            ->icon('heroicon-o-arrow-path')
            ->action(function () {
                $synced = app(\App\Services\GranolaSyncService::class)
                    ->syncRecent(limit: 10);

                \Filament\Notifications\Notification::make()
                    ->title(count($synced) . ' meeting(s) synced from Granola')
                    ->success()
                    ->send();
            }),
    ];
}
```

### Environment Configuration

```dotenv
# .env — Granola MCP connection
GRANOLA_MCP_URL=http://localhost:3333       # Local MCP server endpoint
GRANOLA_MCP_TRANSPORT=http                   # http | stdio
```

```php
// config/services.php

'granola' => [
    'mcp_url'   => env('GRANOLA_MCP_URL', 'http://localhost:3333'),
    'transport' => env('GRANOLA_MCP_TRANSPORT', 'http'),
],
```

### Running the Granola MCP Server

The Granola MCP server runs locally alongside your Laravel app. Install and start it:

```bash
# Install the community Granola MCP server (choose one)
npx granola-mcp             # npm-based, API-backed
# OR
pip install granola-mcp     # Python-based, local cache

# Start with HTTP transport (for Laravel to call)
npx granola-mcp --transport http --port 3333
```

Granola reads credentials from its local config (`~/Library/Application Support/Granola/supabase.json` on macOS). No API keys need to be stored in your Laravel `.env` beyond the MCP server URL.

---

## 3. Agenda Builder

Before every meeting, Solas Rún helps you build a structured agenda. For client meetings, the agenda pulls from open action items, unresolved scope questions, and deferred items ready for revisit. For internal self-meetings, it pulls from your weekly goals, stalled tasks, and personal deferred items.

### Agenda Structure

```
Meeting Agenda
├── Opening (5 min)
│   └── Purpose statement, desired outcomes
├── Standing Items
│   ├── Action item follow-up from last meeting
│   └── Budget/timeline check
├── Main Topics (custom, ordered)
│   ├── Topic 1: [title] — [time allocation]
│   ├── Topic 2: ...
│   └── ...
├── Deferred Items for Review (optional)
│   └── Items from previous meetings ready to revisit
├── New Business
│   └── Anything that emerged before the meeting
└── Close
    ├── Decisions made
    ├── Action items assigned
    └── Next meeting date
```

### MeetingAgenda Model (new table)

```sql
meeting_agendas
├── id
├── user_id
├── project_id (nullable)
├── client_meeting_id (nullable) -- linked after the meeting happens
├── title
├── client_type (external | self)
├── client_name (nullable)
├── scheduled_for (datetime)
├── purpose (text) -- why are we meeting?
├── desired_outcomes (json) -- what does success look like?
├── status (draft | sent | in-progress | complete | cancelled)
├── ai_suggested_topics (json)
├── notes (text, nullable)
└── timestamps
```

```sql
agenda_items
├── id
├── agenda_id
├── title
├── description (nullable)
├── item_type (topic | action-followup | deferred-review | decision | new-business)
├── source_type (nullable) -- task | deferred_item | scope_item | manual
├── source_id (nullable)
├── time_allocation_minutes (nullable)
├── sort_order
├── status (pending | discussed | deferred | resolved)
├── outcome_notes (text, nullable) -- filled in during/after the meeting
└── timestamps
```

---

## 4. Meeting Intelligence Extraction

After a transcript is received and analyzed, Solas Rún extracts the following categories of intelligence:

### Extraction Categories

| Category | What it captures | Where it goes |
|----------|-----------------|---------------|
| **Done Items** | Work confirmed as complete in the meeting | `meeting_done_items` |
| **Decisions** | Explicit choices made | `client_meetings.decisions` |
| **Action Items** | Next steps assigned | Tasks created or updated |
| **In-Scope** | Things explicitly confirmed as included | `meeting_scope_items` (type: in-scope) |
| **Out-of-Scope** | Things explicitly excluded | `meeting_scope_items` (type: out-of-scope) |
| **Deferred Items** | "Not this time" items | `deferred_items` |
| **Wish-List Items** | Ambitious ideas beyond current resources | `deferred_items` (type: future-vision) |
| **Resource Signals** | Mentions of budget, time, capability constraints | `meeting_resource_signals` |
| **Risks** | Identified concerns | `meeting_scope_items` (type: risk) |
| **Assumptions** | Unstated presumptions | `meeting_scope_items` (type: assumption) |

---

### Done Item Tracking

Completed work discussed in meetings is tracked as richly as deferred items. A done item record captures *what* was finished, *why it mattered*, and *what it enabled* — creating a cumulative record of delivered value.

```
Client said: "The new checkout flow is really smooth — 
             our cart abandonment is already down 18%."

→ Done Item: "Checkout flow redesign"
→ Outcome: "Cart abandonment reduced 18%"
→ Value delivered: Quantified impact on client's business
→ Linked to: Task #247, Project "E-commerce Redesign"
→ Testimonial flag: This quote is worth saving for proposals
```

This data feeds:
- **Portfolio / case studies** — evidence of delivered value
- **Proposals** — concrete outcomes to reference in future pitches
- **Client relationship timeline** — everything delivered, in order
- **Personal achievement log** — for internal goals, your own progress record

---

## 5. New & Updated Tables

### Updated: `client_meetings`

```php
// New columns to add via migration

$table->enum('client_type', ['external', 'self'])->default('external')->after('user_id');
// 'self' = you are the client — internal planning, goal brainstorming, life reviews

$table->string('source')->nullable()->after('transcript');
// granola | manual

$table->string('granola_meeting_id')->nullable()->unique()->after('source');
// ID from Granola — used for sync and resync

$table->enum('transcription_status', [
    'pending',    // Meeting created, no transcript yet
    'processing', // Transcript received, AI analysis running
    'complete',   // Analysis done, items extracted
    'failed',     // Transcription or analysis failed
])->default('pending')->after('transcription_external_id');

$table->timestamp('transcript_received_at')->nullable();
$table->timestamp('analysis_completed_at')->nullable();
```

---

### New: `meeting_done_items`

```php
Schema::create('meeting_done_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('meeting_id')->constrained('client_meetings')->cascadeOnDelete();
    $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();

    $table->string('title');
    $table->text('description')->nullable();

    // What did completing this achieve?
    $table->text('outcome')->nullable();
    $table->string('outcome_metric')->nullable();
    // e.g. "Cart abandonment down 18%", "Page speed improved 40%"

    // Client's exact words about the completed work
    $table->text('client_reaction')->nullable();
    $table->string('client_quote')->nullable();

    $table->decimal('value_delivered', 10, 2)->nullable();
    // Estimated $ value of the outcome to the client

    $table->boolean('save_as_testimonial')->default(false);
    $table->boolean('save_for_portfolio')->default(false);
    $table->boolean('save_for_case_study')->default(false);

    $table->timestamps();

    $table->index('user_id');
    $table->index('meeting_id');
});
```

---

### New: `meeting_resource_signals`

Captures every mention of resource constraints — budget, time, capability, technology — that explains why something was deferred or scoped out.

```php
Schema::create('meeting_resource_signals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('meeting_id')->constrained('client_meetings')->cascadeOnDelete();
    $table->foreignId('deferred_item_id')->nullable()
          ->constrained('deferred_items')->nullOnDelete();

    $table->enum('resource_type', [
        'budget',       // Money / financial resources
        'time',         // Bandwidth / availability
        'technology',   // Platform, tools, infrastructure not yet in place
        'capability',   // Skills or knowledge not yet developed
        'team',         // Headcount or specialist not available
        'readiness',    // Client or self not psychologically/strategically ready
        'dependency',   // Waiting on something else to complete first
    ]);

    $table->text('description');
    $table->string('client_quote')->nullable();

    // When does this constraint lift?
    $table->string('constraint_timeline')->nullable();
    // e.g. "After Q1", "When we hire a developer", "Next year's budget"

    $table->boolean('creates_revisit_opportunity')->default(true);

    $table->timestamps();

    $table->index(['meeting_id', 'resource_type']);
});
```

---

### New: `meeting_agendas`

```php
Schema::create('meeting_agendas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('client_meeting_id')->nullable()
          ->constrained('client_meetings')->nullOnDelete();

    $table->string('title');
    $table->enum('client_type', ['external', 'self'])->default('external');
    $table->string('client_name')->nullable();
    $table->dateTime('scheduled_for')->nullable();
    $table->text('purpose')->nullable();
    $table->json('desired_outcomes')->nullable();
    $table->enum('status', [
        'draft', 'ready', 'in-progress', 'complete', 'cancelled'
    ])->default('draft');
    $table->json('ai_suggested_topics')->nullable();
    $table->text('notes')->nullable();

    $table->timestamps();

    $table->index('user_id');
    $table->index('scheduled_for');
});
```

---

### New: `agenda_items`

```php
Schema::create('agenda_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('agenda_id')->constrained('meeting_agendas')->cascadeOnDelete();

    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('item_type', [
        'topic',
        'action-followup',
        'deferred-review',
        'decision',
        'new-business',
        'budget-check',
    ])->default('topic');

    // Source linking — where did this agenda item come from?
    $table->string('source_type')->nullable();
    // 'task' | 'deferred_item' | 'scope_item' | 'manual'
    $table->unsignedBigInteger('source_id')->nullable();

    $table->unsignedSmallInteger('time_allocation_minutes')->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);

    $table->enum('status', [
        'pending', 'discussed', 'deferred', 'resolved', 'skipped'
    ])->default('pending');

    $table->text('outcome_notes')->nullable();

    $table->timestamps();
});
```

---

## 6. Migrations

### Run All New Migrations

```bash
# Update existing tables
php artisan make:migration add_transcription_fields_to_client_meetings_table
php artisan make:migration add_client_type_to_client_meetings_table

# New tables
php artisan make:migration create_meeting_done_items_table
php artisan make:migration create_meeting_resource_signals_table
php artisan make:migration create_meeting_agendas_table
php artisan make:migration create_agenda_items_table
```

See section 5 for the full schema definitions to use in each migration.

---

## 7. Models

### ClientMeeting — Updated

```php
// New fillable fields
protected $fillable = [
    // ... existing fields ...
    'client_type',
    'source',
    'granola_meeting_id',
    'transcription_status',
    'transcript_received_at',
    'analysis_completed_at',
];

// New relationships
public function doneItems(): HasMany
{
    return $this->hasMany(MeetingDoneItem::class, 'meeting_id');
}

public function resourceSignals(): HasMany
{
    return $this->hasMany(MeetingResourceSignal::class, 'meeting_id');
}

public function agenda(): HasOne
{
    return $this->hasOne(MeetingAgenda::class, 'client_meeting_id');
}

// Helpers
public function isSelfMeeting(): bool
{
    return $this->client_type === 'self';
}

public function isAnalyzed(): bool
{
    return $this->transcription_status === 'complete';
}

public function clientLabel(): string
{
    return $this->isSelfMeeting()
        ? 'Internal — ' . auth()->user()->name
        : ($this->project?->client_name ?? 'Unknown Client');
}
```

---

### MeetingAgenda Model

```php
// app/Models/MeetingAgenda.php

use App\Traits\HasTenant;

class MeetingAgenda extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id', 'project_id', 'client_meeting_id',
        'title', 'client_type', 'client_name', 'scheduled_for',
        'purpose', 'desired_outcomes', 'status',
        'ai_suggested_topics', 'notes',
    ];

    protected $casts = [
        'scheduled_for'        => 'datetime',
        'desired_outcomes'     => 'array',
        'ai_suggested_topics'  => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AgendaItem::class)->orderBy('sort_order');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(ClientMeeting::class, 'client_meeting_id');
    }

    public function previousMeetings(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->project_id) return collect();

        return ClientMeeting::where('project_id', $this->project_id)
            ->where('meeting_date', '<', $this->scheduled_for ?? now())
            ->orderByDesc('meeting_date')
            ->get();
    }

    public function openActionItems(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->project_id) return collect();

        return \App\Models\Task::where('project_id', $this->project_id)
            ->whereIn('status', ['todo', 'in-progress'])
            ->orderBy('priority', 'desc')
            ->get();
    }

    public function deferredItemsForReview(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->project_id) return collect();

        return \App\Models\DeferredItem::where('project_id', $this->project_id)
            ->dueForReview()
            ->orderByDesc('estimated_value')
            ->get();
    }
}
```

---

### MeetingDoneItem Model

```php
// app/Models/MeetingDoneItem.php

use App\Traits\HasTenant;

class MeetingDoneItem extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id', 'meeting_id', 'task_id', 'project_id',
        'title', 'description', 'outcome', 'outcome_metric',
        'client_reaction', 'client_quote', 'value_delivered',
        'save_as_testimonial', 'save_for_portfolio', 'save_for_case_study',
    ];

    protected $casts = [
        'save_as_testimonial' => 'boolean',
        'save_for_portfolio'  => 'boolean',
        'save_for_case_study' => 'boolean',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(ClientMeeting::class, 'meeting_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
```

---

## 8. MeetingIntelligenceService

The central service that reads a meeting transcript and extracts structured intelligence across all categories.

```php
// app/Services/MeetingIntelligenceService.php

namespace App\Services;

use App\Models\{
    ClientMeeting, MeetingDoneItem, MeetingResourceSignal,
    MeetingScopeItem, DeferredItem, Task
};

class MeetingIntelligenceService
{
    public function __construct(protected AiService $ai) {}

    /**
     * Full analysis pipeline for a meeting transcript.
     */
    public function analyze(ClientMeeting $meeting): void
    {
        $meeting->update(['transcription_status' => 'processing']);

        try {
            $extracted = $this->extractAll($meeting);

            $this->persistDoneItems($meeting, $extracted['done_items'] ?? []);
            $this->persistScopeItems($meeting, $extracted['scope_items'] ?? []);
            $this->persistDeferredItems($meeting, $extracted['deferred_items'] ?? []);
            $this->persistResourceSignals($meeting, $extracted['resource_signals'] ?? []);
            $this->persistActionItems($meeting, $extracted['action_items'] ?? []);

            $meeting->update([
                'summary'                => $extracted['summary'] ?? null,
                'decisions'              => $extracted['decisions'] ?? null,
                'ai_scope_analysis'      => $extracted['scope_analysis'] ?? null,
                'transcription_status'   => 'complete',
                'analysis_completed_at'  => now(),
            ]);

            $this->notifyUser($meeting, $extracted);

        } catch (\Exception $e) {
            $meeting->update(['transcription_status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Call AI once with a comprehensive extraction prompt.
     * Returns all categories in a single structured JSON response.
     */
    private function extractAll(ClientMeeting $meeting): array
    {
        $clientContext = $meeting->isSelfMeeting()
            ? "This is an internal planning session. The 'client' is the user themselves."
            : "This is a meeting with an external client: {$meeting->project?->client_name}.";

        $prompt = <<<PROMPT
You are a project intelligence analyst. Analyze this meeting transcript and extract structured data.

MEETING: "{$meeting->title}"
DATE: {$meeting->meeting_date->format('M j, Y')}
TYPE: {$meeting->meeting_type}
PROJECT: {$meeting->project?->name ?? 'No linked project'}
CONTEXT: {$clientContext}

TRANSCRIPT:
{$meeting->transcript}

Extract the following in a single JSON response:

{
  "summary": "3–4 sentence meeting summary",

  "decisions": "Bulleted list of explicit decisions made",

  "done_items": [
    {
      "title": "What was confirmed as complete",
      "outcome": "What impact or result this had",
      "outcome_metric": "Quantified result if mentioned (e.g. '18% improvement')",
      "client_quote": "Exact words if the client acknowledged the work",
      "value_delivered": null or number,
      "save_as_testimonial": true if the quote is praise-worthy
    }
  ],

  "action_items": [
    {
      "title": "Next step",
      "assigned_to": "client | user | both",
      "due": "date string or null",
      "priority": "high | medium | low"
    }
  ],

  "scope_items": [
    {
      "description": "What was discussed",
      "type": "in-scope | out-of-scope | deferred | assumption | risk",
      "client_quote": "Exact words if available",
      "confirmed_with_client": true or false
    }
  ],

  "deferred_items": [
    {
      "title": "What was set aside",
      "reason": "budget | timeline | priority | client-not-ready | scope-control | awaiting-decision | technology | personal",
      "opportunity_type": "phase-2 | upsell | upgrade | new-project | retainer | product-feature | personal-goal | none",
      "client_quote": "Exact words explaining the deferral",
      "estimated_value": null or number,
      "revisit_trigger": "What would bring this back to the table",
      "why_it_matters": "Why this is worth keeping"
    }
  ],

  "resource_signals": [
    {
      "resource_type": "budget | time | technology | capability | team | readiness | dependency",
      "description": "What constraint was mentioned",
      "client_quote": "Exact words",
      "constraint_timeline": "When this constraint might lift"
    }
  ],

  "scope_analysis": "A paragraph summary of what is and isn't included in scope, and any risks to budget or timeline"
}

Be conservative with dollar estimates. Only include them if explicitly discussed or strongly implied.
For internal/self meetings, treat 'client' as the user themselves and resource constraints as personal capacity.
PROMPT;

        $response = $this->ai->chat(
            prompt: $prompt,
            type:   'freeform',
            context: ['meeting_id' => $meeting->id]
        );

        // Strip markdown code fences if present
        $clean = preg_replace('/^```json\s*|\s*```$/m', '', trim($response));
        return json_decode($clean, true) ?? [];
    }

    // ── Persistence Methods ────────────────────────────────────────────

    private function persistDoneItems(ClientMeeting $meeting, array $items): void
    {
        foreach ($items as $item) {
            MeetingDoneItem::create([
                'user_id'              => $meeting->user_id,
                'meeting_id'           => $meeting->id,
                'project_id'           => $meeting->project_id,
                'title'                => $item['title'],
                'outcome'              => $item['outcome'] ?? null,
                'outcome_metric'       => $item['outcome_metric'] ?? null,
                'client_quote'         => $item['client_quote'] ?? null,
                'value_delivered'      => $item['value_delivered'] ?? null,
                'save_as_testimonial'  => $item['save_as_testimonial'] ?? false,
            ]);
        }
    }

    private function persistDeferredItems(ClientMeeting $meeting, array $items): void
    {
        $deferralService = app(DeferralService::class);

        foreach ($items as $item) {
            $deferred = DeferredItem::create([
                'user_id'          => $meeting->user_id,
                'meeting_id'       => $meeting->id,
                'project_id'       => $meeting->project_id,
                'title'            => $item['title'],
                'client_name'      => $meeting->isSelfMeeting()
                                        ? 'Internal'
                                        : $meeting->project?->client_name,
                'client_quote'     => $item['client_quote'] ?? null,
                'why_it_matters'   => $item['why_it_matters'] ?? null,
                'deferral_reason'  => $item['reason'] ?? 'priority',
                'opportunity_type' => $item['opportunity_type'] ?? 'none',
                'estimated_value'  => $item['estimated_value'] ?? null,
                'revisit_trigger'  => $item['revisit_trigger'] ?? null,
                'status'           => 'someday',
                'deferred_on'      => $meeting->meeting_date,
                'client_type'      => $meeting->client_type,
            ]);
        }
    }

    private function persistResourceSignals(ClientMeeting $meeting, array $signals): void
    {
        foreach ($signals as $signal) {
            MeetingResourceSignal::create([
                'user_id'               => $meeting->user_id,
                'meeting_id'            => $meeting->id,
                'resource_type'         => $signal['resource_type'],
                'description'           => $signal['description'],
                'client_quote'          => $signal['client_quote'] ?? null,
                'constraint_timeline'   => $signal['constraint_timeline'] ?? null,
            ]);
        }
    }

    private function persistScopeItems(ClientMeeting $meeting, array $items): void
    {
        foreach ($items as $item) {
            MeetingScopeItem::create([
                'user_id'               => $meeting->user_id,
                'meeting_id'            => $meeting->id,
                'description'           => $item['description'],
                'type'                  => $item['type'],
                'client_quote'          => $item['client_quote'] ?? null,
                'confirmed_with_client' => $item['confirmed_with_client'] ?? false,
            ]);
        }
    }

    private function persistActionItems(ClientMeeting $meeting, array $items): void
    {
        foreach ($items as $item) {
            if (in_array($item['assigned_to'] ?? 'user', ['user', 'both'])) {
                Task::create([
                    'user_id'        => $meeting->user_id,
                    'project_id'     => $meeting->project_id,
                    'life_area_id'   => $meeting->project?->life_area_id
                                        ?? \App\Models\LifeArea::where('name', 'Business')->value('id'),
                    'title'          => $item['title'],
                    'status'         => 'todo',
                    'priority'       => $item['priority'] ?? 'medium',
                    'due_date'       => isset($item['due']) ? \Carbon\Carbon::parse($item['due']) : null,
                    'notes'          => "From meeting: {$meeting->title} on {$meeting->meeting_date->format('M j, Y')}",
                    'is_leaf'        => true,
                    'decomposition_status' => 'ready',
                    'two_minute_check'     => false,
                ]);
            }
        }
    }

    private function notifyUser(ClientMeeting $meeting, array $extracted): void
    {
        $doneCount     = count($extracted['done_items'] ?? []);
        $deferredCount = count($extracted['deferred_items'] ?? []);
        $actionCount   = count($extracted['action_items'] ?? []);

        \Filament\Notifications\Notification::make()
            ->title('Meeting analyzed: ' . $meeting->title)
            ->body(
                "{$actionCount} action items · {$doneCount} done items · {$deferredCount} deferred items"
            )
            ->success()
            ->sendToDatabase(auth()->user());
    }
}
```

---

## 9. AgendaService

```php
// app/Services/AgendaService.php

namespace App\Services;

use App\Models\{MeetingAgenda, AgendaItem, ClientMeeting, Project};

class AgendaService
{
    public function __construct(protected AiService $ai) {}

    /**
     * Build a new agenda for an upcoming meeting.
     * Pulls context from previous meetings, open tasks, and deferred items.
     */
    public function buildAgenda(
        string  $title,
        string  $clientType = 'external',
        ?int    $projectId  = null,
        ?string $clientName = null,
        ?string $scheduledFor = null,
        ?string $purpose    = null
    ): MeetingAgenda {

        $agenda = MeetingAgenda::create([
            'user_id'       => auth()->id(),
            'project_id'    => $projectId,
            'title'         => $title,
            'client_type'   => $clientType,
            'client_name'   => $clientName,
            'scheduled_for' => $scheduledFor,
            'purpose'       => $purpose,
            'status'        => 'draft',
        ]);

        // Auto-populate standing items
        $this->addStandingItems($agenda);

        // Add open action items from previous meeting
        $this->addOpenActionItems($agenda);

        // Add deferred items due for review
        $this->addDeferredReviewItems($agenda);

        // Generate AI-suggested topics
        $this->suggestTopics($agenda);

        return $agenda;
    }

    private function addStandingItems(MeetingAgenda $agenda): void
    {
        $standaloneItems = [
            [
                'title'     => 'Purpose & desired outcomes',
                'item_type' => 'topic',
                'time_allocation_minutes' => 5,
                'sort_order' => 0,
            ],
        ];

        if ($agenda->project_id) {
            $standaloneItems[] = [
                'title'     => 'Budget & timeline check',
                'item_type' => 'budget-check',
                'time_allocation_minutes' => 5,
                'sort_order' => 1,
            ];
        }

        foreach ($standaloneItems as $item) {
            AgendaItem::create(array_merge($item, ['agenda_id' => $agenda->id]));
        }
    }

    private function addOpenActionItems(MeetingAgenda $agenda): void
    {
        if (!$agenda->project_id) return;

        $openTasks = $agenda->openActionItems()->take(5);
        $sort = 10;

        foreach ($openTasks as $task) {
            AgendaItem::create([
                'agenda_id'   => $agenda->id,
                'title'       => 'Follow-up: ' . $task->title,
                'item_type'   => 'action-followup',
                'source_type' => 'task',
                'source_id'   => $task->id,
                'sort_order'  => $sort++,
            ]);
        }
    }

    private function addDeferredReviewItems(MeetingAgenda $agenda): void
    {
        $deferredItems = $agenda->deferredItemsForReview()->take(3);
        $sort = 50;

        foreach ($deferredItems as $item) {
            AgendaItem::create([
                'agenda_id'   => $agenda->id,
                'title'       => 'Revisit: ' . $item->title,
                'description' => $item->revisit_trigger
                    ? "Trigger: {$item->revisit_trigger}"
                    : null,
                'item_type'   => 'deferred-review',
                'source_type' => 'deferred_item',
                'source_id'   => $item->id,
                'sort_order'  => $sort++,
            ]);
        }
    }

    /**
     * Use AI to suggest additional agenda topics based on project history
     * and the stated purpose of the meeting.
     */
    public function suggestTopics(MeetingAgenda $agenda): void
    {
        $previousMeetings = $agenda->previousMeetings()
            ->take(3)
            ->map(fn ($m) =>
                "- {$m->meeting_date->format('M j')}: {$m->title} ({$m->meeting_type})"
            )
            ->join("\n");

        $clientContext = $agenda->client_type === 'self'
            ? 'This is an internal planning session. Treat as a self-coaching meeting.'
            : "External client meeting for project: {$agenda->project?->name}";

        $prompt = <<<PROMPT
You are helping prepare a meeting agenda.

MEETING: "{$agenda->title}"
PURPOSE: {$agenda->purpose}
CLIENT CONTEXT: {$clientContext}

PREVIOUS MEETINGS:
{$previousMeetings}

Suggest 3–5 specific agenda topics that would make this meeting productive.
For each topic, suggest a time allocation.

Respond as JSON:
[
  {
    "title": "Topic title",
    "description": "Why this topic matters for this meeting",
    "time_allocation_minutes": 10
  }
]
PROMPT;

        $response = $this->ai->chat($prompt, 'freeform');
        $clean    = preg_replace('/^```json\s*|\s*```$/m', '', trim($response));
        $topics   = json_decode($clean, true) ?? [];

        $agenda->update(['ai_suggested_topics' => $topics]);
    }

    /**
     * Mark an agenda as complete and link it to the resulting meeting record.
     */
    public function linkToMeeting(MeetingAgenda $agenda, ClientMeeting $meeting): void
    {
        $agenda->update([
            'client_meeting_id' => $meeting->id,
            'status'            => 'complete',
        ]);
    }
}
```

---

## 10. Filament Resources

### ClientMeetingResource — Updated

Add new tabs to the form for Done Items, Resource Signals, and Agenda:

```php
// Updated form schema with Tabs

use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;

public static function form(Form $form): Form
{
    return $form->schema([
        Tabs::make()->tabs([

            Tab::make('Meeting Details')->schema([
                Grid::make(2)->schema([
                    Select::make('client_type')
                        ->label('Meeting With')
                        ->options([
                            'external' => '🤝 External Client',
                            'self'     => '🪞 Myself (Internal)',
                        ])
                        ->default('external')
                        ->live()
                        ->required(),

                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->nullable(),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('title')->required(),
                    DatePicker::make('meeting_date')->required()->default(today()),
                ]),

                Grid::make(2)->schema([
                    Select::make('meeting_type')
                        ->options([
                            'discovery'     => '🔍 Discovery',
                            'requirements'  => '📋 Requirements',
                            'check-in'      => '✅ Check-in',
                            'brainstorm'    => '💡 Brainstorm',
                            'review'        => '👀 Review',
                            'planning'      => '📅 Planning',
                            'retrospective' => '🔁 Retrospective',
                            'handoff'       => '🤝 Handoff',
                        ])
                        ->default('check-in'),

                    Select::make('transcription_status')
                        ->options([
                            'pending'    => 'Pending',
                            'processing' => 'Processing',
                            'complete'   => 'Complete',
                            'failed'     => 'Failed',
                        ])
                        ->disabled(),
                ]),

                \Filament\Forms\Components\TagsInput::make('attendees')
                    ->columnSpanFull(),
            ]),

            Tab::make('Transcript')->schema([
                Textarea::make('transcript')
                    ->label('Transcript / Notes')
                    ->rows(15)
                    ->helperText('Synced from Granola automatically, or paste a transcript manually.')
                    ->columnSpanFull(),

                Textarea::make('summary')->rows(4)->columnSpanFull(),
                Textarea::make('decisions')->rows(3)->columnSpanFull(),
            ]),

            Tab::make('Scope & Actions')->schema([
                Placeholder::make('ai_scope_analysis')
                    ->content(fn ($record) =>
                        $record?->ai_scope_analysis ?? 'Save with a transcript to generate scope analysis.'
                    )
                    ->columnSpanFull(),

                Textarea::make('action_items')->rows(4)->columnSpanFull(),
            ]),

        ])->columnSpanFull(),
    ]);
}
```

---

### MeetingAgendaResource

```php
protected static ?string $model = MeetingAgenda::class;
protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
protected static ?string $navigationGroup = 'Goals & Projects';
protected static ?string $navigationLabel = 'Meeting Agendas';
protected static ?int $navigationSort = 9;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Meeting Details')->schema([
            TextInput::make('title')->required()->columnSpanFull(),

            Grid::make(3)->schema([
                Select::make('client_type')
                    ->label('Meeting With')
                    ->options([
                        'external' => '🤝 External Client',
                        'self'     => '🪞 Myself',
                    ])
                    ->default('external')
                    ->live(),

                TextInput::make('client_name')
                    ->label('Client Name')
                    ->nullable()
                    ->visible(fn ($get) => $get('client_type') === 'external'),

                \Filament\Forms\Components\DateTimePicker::make('scheduled_for')
                    ->label('Scheduled For'),
            ]),

            Grid::make(2)->schema([
                Select::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->nullable(),

                Select::make('status')
                    ->options([
                        'draft'       => 'Draft',
                        'ready'       => 'Ready to Send',
                        'in-progress' => 'In Progress',
                        'complete'    => 'Complete',
                        'cancelled'   => 'Cancelled',
                    ])
                    ->default('draft'),
            ]),

            Textarea::make('purpose')
                ->label('Purpose of This Meeting')
                ->placeholder('What do we need to accomplish in this meeting?')
                ->rows(2)
                ->columnSpanFull(),

            \Filament\Forms\Components\TagsInput::make('desired_outcomes')
                ->label('Desired Outcomes')
                ->placeholder('Add an outcome')
                ->helperText('What does a successful meeting look like?')
                ->columnSpanFull(),
        ]),

        Section::make('AI Suggested Topics')->schema([
            Placeholder::make('ai_suggested_topics')
                ->label('')
                ->content(fn ($record) =>
                    $record?->ai_suggested_topics
                        ? collect($record->ai_suggested_topics)
                            ->map(fn ($t) => "• **{$t['title']}** ({$t['time_allocation_minutes']}min) — {$t['description']}")
                            ->join("\n")
                        : 'Save the agenda to generate AI topic suggestions.'
                )
                ->columnSpanFull(),
        ]),
    ]);
}

public static function getRelationManagers(): array
{
    return [
        \App\Filament\Resources\MeetingAgendaResource\RelationManagers\AgendaItemsRelationManager::class,
    ];
}
```

---

## 11. AI Integration Points

### 1. Full Transcript Analysis

**When:** Meeting synced from Granola (automatic or manual sync) or transcript pasted manually.
**What it does:** Single-pass extraction of all intelligence categories — done items, action items, scope items, deferred items, resource signals, decisions, and summary.
**Stores in:** All meeting-related tables.

---

### 2. Agenda Topic Suggestion

**When:** New agenda is created with a purpose statement and project context.
**What it does:** Reviews previous meetings and open items, suggests 3–5 specific topics with time allocations.
**Stores in:** `meeting_agendas.ai_suggested_topics`

---

### 3. Internal Goal Brainstorm

**When:** User creates a self-meeting of type `brainstorm` or `planning`.
**What it does:** Acts as a thinking partner — asks clarifying questions to help define the goal, surfaces relevant past context, and suggests a structured plan.

```php
public function facilitateBrainstorm(
    string $goalIdea,
    array  $lifeAreaContext,
    array  $existingGoals
): string {

    $existingGoalList = collect($existingGoals)
        ->map(fn ($g) => "- {$g['title']} ({$g['status']})")
        ->join("\n");

    $prompt = <<<PROMPT
You are a life coach and strategic thinking partner. The user wants to brainstorm and develop a goal.

GOAL IDEA: "{$goalIdea}"
LIFE AREA: {$lifeAreaContext['name']}

THEIR EXISTING GOALS IN THIS AREA:
{$existingGoalList}

Help them develop this idea by:
1. Asking 2–3 clarifying questions that surface the real motivation
2. Helping articulate WHAT success looks like (specific and measurable)
3. Surfacing what RESOURCES they'll need (time, money, capability, technology)
4. Identifying what might need to happen FIRST before this goal is achievable
5. Suggesting whether this is a 90-day, 1-year, 3-year, or lifetime goal

Be conversational, curious, and honest. Push back gently if the goal seems vague.
PROMPT;

    return $this->chat($prompt, 'goal-breakdown');
}
```

---

### 4. Personal Resource Readiness Assessment

**When:** User is considering activating a deferred personal goal.
**What it does:** Reviews current resource levels (based on habits, tasks, and goals) and assesses whether the user is ready to take on the goal.

```php
public function assessPersonalReadiness(
    \App\Models\DeferredItem $item,
    array $currentResources
): string {

    $prompt = <<<PROMPT
A user is considering activating a deferred personal goal.

DEFERRED GOAL: "{$item->title}"
ORIGINALLY DEFERRED BECAUSE: {$item->deferral_reason}
WHY IT MATTERS TO THEM: {$item->why_it_matters}
DEFERRED ON: {$item->deferred_on->format('M j, Y')}

CURRENT RESOURCE STATUS:
- Active goals: {$currentResources['active_goals']}
- Current habit load: {$currentResources['habit_count']} habits
- Available weekly capacity (estimated): {$currentResources['estimated_hours']} hours
- Recent energy average: {$currentResources['avg_energy']} / 5
- Recent focus average: {$currentResources['avg_focus']} / 5

Assess:
1. Is now a good time to activate this goal, given their current load?
2. What resource specifically was missing when they deferred it — is it likely available now?
3. What would need to be true for this to be the right time?
4. If they activate it, what should the first 3 steps be?

Be honest and direct. If they're already stretched thin, say so.
PROMPT;

    return $this->chat($prompt, 'freeform');
}
```

---

*Solas Rún • Version 1.2 • Meeting Intelligence*
