# SOLAS RÚN
### *Filament Resources*
**Technical Reference v1.1**

---

## Overview

This document covers all twelve Filament Resources for Solas Rún. Each resource defines the **table** (list view), **form** (create/edit), **filters**, **actions**, and **navigation** configuration. All resources extend the `HasTenant` scope automatically — no additional scoping is needed inside the resource.

---

## Table of Contents

1. [Base Conventions](#1-base-conventions)
2. [LifeAreaResource](#2-lifearearesource)
3. [GoalResource](#3-goalresource)
4. [MilestoneResource](#4-milestoneresource)
5. [ProjectResource](#5-projectresource)
6. [TaskResource](#6-taskresource)
7. [HabitResource](#7-habitresource)
8. [HabitLogResource](#8-habitlogresource)
9. [DailyPlanResource](#9-dailyplanresource)
10. [TimeBlockResource](#10-timeblockresource)
11. [JournalEntryResource](#11-journalentryresource)
12. [WeeklyReviewResource](#12-weeklyreviewresource)
13. [AiInteractionResource](#13-aiinteractionresource)
14. [MeetingDoneItemResource](#14-meetingdoneitemresource)
15. [Navigation Registration](#15-navigation-registration)

---

## 1. Base Conventions

### Generate All Resources

```bash
php artisan make:filament-resource LifeArea --generate
php artisan make:filament-resource Goal --generate
php artisan make:filament-resource Milestone --generate
php artisan make:filament-resource Project --generate
php artisan make:filament-resource Task --generate
php artisan make:filament-resource Habit --generate
php artisan make:filament-resource HabitLog --generate
php artisan make:filament-resource DailyPlan --generate
php artisan make:filament-resource TimeBlock --generate
php artisan make:filament-resource JournalEntry --generate
php artisan make:filament-resource WeeklyReview --generate
php artisan make:filament-resource AiInteraction --generate
php artisan make:filament-resource ClientMeeting --generate
php artisan make:filament-resource MeetingAgenda --generate
php artisan make:filament-resource MeetingDoneItem --generate
php artisan make:filament-resource DeferredItem --generate
php artisan make:filament-resource OpportunityPipeline --generate
php artisan make:filament-resource ProjectBudget --generate
php artisan make:filament-resource TimeEntry --generate
```

The `--generate` flag scaffolds the table columns and form fields from your migration. Use the generated output as a starting point, then replace with the refined versions below.

### ClientMeetingResource — Updated Form

> **Note:** The `ClientMeetingResource` form should use the tabbed version defined in `solas-run-meeting-intelligence.md` Section 10. Key additions include: `client_type` selector (External Client / Myself), `transcription_status` read-only indicator, Tabs for Meeting Details / Transcript / Scope & Actions, and relation managers for `DoneItemsRelationManager`, `ResourceSignalsRelationManager`, `ScopeItemsRelationManager`.

---

### Shared Imports

Add these imports to every resource file as needed:

```php
use Filament\Forms\Components\{
    Section, Grid, TextInput, Textarea, Select, DatePicker,
    Toggle, ColorPicker, MarkdownEditor, Repeater,
    TimePicker, RichEditor, Placeholder
};
use Filament\Tables\Columns\{
    TextColumn, BadgeColumn, IconColumn, ColorColumn, ToggleColumn
};
use Filament\Tables\Filters\{SelectFilter, Filter, TernaryFilter};
use Filament\Tables\Actions\{EditAction, DeleteAction, ViewAction};
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
```

---

### Life Area Color Helper

A shared helper used across multiple resources to render a colored badge for the life area:

```php
// app/Filament/Support/LifeAreaBadge.php

namespace App\Filament\Support;

use App\Models\LifeArea;

class LifeAreaBadge
{
    public static function getOptions(): array
    {
        return LifeArea::orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn($area) => [$area->id => $area->icon . ' ' . $area->name])
            ->toArray();
    }
}
```

---

## 2. LifeAreaResource

The foundational resource. Users customize their six life areas — names, icons, and colors.

```bash
# Location
app/Filament/Resources/LifeAreaResource.php
```

```php
namespace App\Filament\Resources;

use App\Filament\Resources\LifeAreaResource\Pages;
use App\Models\LifeArea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, Grid, TextInput, Textarea, ColorPicker};
use Filament\Tables\Columns\{TextColumn, ColorColumn};
use Filament\Tables\Actions\{EditAction, DeleteAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};

class LifeAreaResource extends Resource
{
    protected static ?string $model = LifeArea::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Life Areas';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Life Area')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(50)
                        ->placeholder('e.g. Creative'),

                    TextInput::make('icon')
                        ->label('Icon (emoji or Heroicon)')
                        ->placeholder('🎨 or heroicon-o-star')
                        ->required(),
                ]),

                Grid::make(2)->schema([
                    ColorPicker::make('color_hex')
                        ->label('Colour')
                        ->required(),

                    TextInput::make('sort_order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(0),
                ]),

                Textarea::make('description')
                    ->rows(2)
                    ->placeholder('What does this area of your life cover?')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color_hex')
                    ->label(''),

                TextColumn::make('icon')
                    ->label('')
                    ->size('lg'),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->limit(60)
                    ->color('gray'),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLifeAreas::route('/'),
            'create' => Pages\CreateLifeArea::route('/create'),
            'edit'   => Pages\EditLifeArea::route('/{record}/edit'),
        ];
    }
}
```

---

## 3. GoalResource

The heart of the system. Goals drive everything else — daily actions, habits, projects, and AI suggestions all connect back here.

```php
namespace App\Filament\Resources;

use App\Filament\Resources\GoalResource\Pages;
use App\Filament\Support\LifeAreaBadge;
use App\Models\Goal;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{
    Section, Grid, TextInput, Textarea, Select,
    DatePicker, Placeholder
};
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\{EditAction, DeleteAction, ViewAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};

class GoalResource extends Resource
{
    protected static ?string $model = Goal::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Goals & Projects';
    protected static ?string $navigationLabel = 'Goals';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Goal')->schema([
                Grid::make(2)->schema([
                    Select::make('life_area_id')
                        ->label('Life Area')
                        ->options(LifeAreaBadge::getOptions())
                        ->required()
                        ->searchable(),

                    Select::make('horizon')
                        ->options([
                            '90-day'   => '90 Days',
                            '1-year'   => '1 Year',
                            '3-year'   => '3 Years',
                            'lifetime' => 'Lifetime',
                        ])
                        ->required()
                        ->default('1-year'),
                ]),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('What do you want to achieve?')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(3)
                    ->placeholder('Describe the goal in more detail...')
                    ->columnSpanFull(),

                Textarea::make('why')
                    ->label('Why does this matter?')
                    ->rows(3)
                    ->helperText('Your motivation. This is shown during your daily planning session.')
                    ->placeholder('Because...')
                    ->columnSpanFull(),
            ]),

            Section::make('Status & Progress')->schema([
                Grid::make(3)->schema([
                    Select::make('status')
                        ->options([
                            'active'    => 'Active',
                            'paused'    => 'Paused',
                            'achieved'  => 'Achieved ✅',
                            'abandoned' => 'Abandoned',
                        ])
                        ->default('active')
                        ->required(),

                    DatePicker::make('target_date')
                        ->label('Target Date')
                        ->nullable(),

                    TextInput::make('progress_percent')
                        ->label('Progress %')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(0),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lifeArea.name')
                    ->label('Area')
                    ->badge()
                    ->color(fn ($record) => 'primary')
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('horizon')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        '90-day'   => 'warning',
                        '1-year'   => 'info',
                        '3-year'   => 'success',
                        'lifetime' => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'active'    => 'success',
                        'paused'    => 'warning',
                        'achieved'  => 'info',
                        'abandoned' => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('target_date')
                    ->label('Target')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn ($record) =>
                        $record->target_date?->isPast() && $record->status === 'active'
                            ? 'danger'
                            : 'gray'
                    ),
            ])
            ->defaultSort('status')
            ->filters([
                SelectFilter::make('life_area_id')
                    ->label('Life Area')
                    ->relationship('lifeArea', 'name'),

                SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active',
                        'paused'    => 'Paused',
                        'achieved'  => 'Achieved',
                        'abandoned' => 'Abandoned',
                    ]),

                SelectFilter::make('horizon')
                    ->options([
                        '90-day'   => '90 Days',
                        '1-year'   => '1 Year',
                        '3-year'   => '3 Years',
                        'lifetime' => 'Lifetime',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\GoalResource\RelationManagers\MilestonesRelationManager::class,
            \App\Filament\Resources\GoalResource\RelationManagers\TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGoals::route('/'),
            'create' => Pages\CreateGoal::route('/create'),
            'view'   => Pages\ViewGoal::route('/{record}'),
            'edit'   => Pages\EditGoal::route('/{record}/edit'),
        ];
    }
}
```

### Goal Relation Managers

#### MilestonesRelationManager

```bash
php artisan make:filament-relation-manager GoalResource milestones title
```

```php
// app/Filament/Resources/GoalResource/RelationManagers/MilestonesRelationManager.php

public function form(Form $form): Form
{
    return $form->schema([
        TextInput::make('title')->required()->columnSpanFull(),
        Grid::make(2)->schema([
            DatePicker::make('due_date')->nullable(),
            Select::make('status')
                ->options(['pending' => 'Pending', 'complete' => 'Complete ✅'])
                ->default('pending'),
        ]),
    ]);
}

public function table(Table $table): Table
{
    return $table
        ->reorderable('sort_order')
        ->columns([
            TextColumn::make('title')->searchable()->wrap(),
            TextColumn::make('due_date')->date('M j, Y')->sortable(),
            TextColumn::make('status')->badge()
                ->color(fn (string $state) => $state === 'complete' ? 'success' : 'warning'),
        ])
        ->actions([EditAction::make(), DeleteAction::make()])
        ->headerActions([\Filament\Tables\Actions\CreateAction::make()]);
}
```

#### TasksRelationManager

```bash
php artisan make:filament-relation-manager GoalResource tasks title
```

```php
public function form(Form $form): Form
{
    return $form->schema([
        TextInput::make('title')->required()->columnSpanFull(),
        Grid::make(3)->schema([
            Select::make('status')
                ->options([
                    'todo' => 'To Do', 'in-progress' => 'In Progress',
                    'done' => 'Done', 'deferred' => 'Deferred',
                ])->default('todo'),
            Select::make('priority')
                ->options([
                    'low' => 'Low', 'medium' => 'Medium',
                    'high' => 'High', 'critical' => 'Critical',
                ])->default('medium'),
            DatePicker::make('due_date')->nullable(),
        ]),
    ]);
}
```

---

## 4. MilestoneResource

Milestones are primarily managed via the Goal relation manager above, but this resource provides a global view for tracking across all goals.

```php
protected static ?string $model = Milestone::class;
protected static ?string $navigationIcon = 'heroicon-o-trophy';
protected static ?string $navigationGroup = 'Goals & Projects';
protected static ?string $navigationLabel = 'Milestones';
protected static ?int $navigationSort = 2;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make()->schema([
            Select::make('goal_id')
                ->label('Goal')
                ->relationship('goal', 'title')
                ->searchable()
                ->required()
                ->columnSpanFull(),

            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                DatePicker::make('due_date')->nullable(),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'complete' => 'Complete ✅'])
                    ->default('pending'),
            ]),
        ]),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('goal.title')->label('Goal')->searchable()->limit(40),
            TextColumn::make('title')->searchable()->wrap(),
            TextColumn::make('due_date')->date('M j, Y')->sortable(),
            TextColumn::make('status')->badge()
                ->color(fn (string $state) => $state === 'complete' ? 'success' : 'warning'),
        ])
        ->defaultSort('due_date')
        ->filters([
            SelectFilter::make('status')
                ->options(['pending' => 'Pending', 'complete' => 'Complete']),
        ])
        ->actions([EditAction::make(), DeleteAction::make()]);
}
```

---

## 5. ProjectResource

Projects track ongoing work — both personal and client-facing.

```php
protected static ?string $model = Project::class;
protected static ?string $navigationIcon = 'heroicon-o-briefcase';
protected static ?string $navigationGroup = 'Goals & Projects';
protected static ?string $navigationLabel = 'Projects';
protected static ?int $navigationSort = 3;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Project Details')->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('life_area_id')
                    ->label('Life Area')
                    ->options(LifeAreaBadge::getOptions())
                    ->required()
                    ->searchable(),

                Select::make('goal_id')
                    ->label('Linked Goal (optional)')
                    ->relationship('goal', 'title')
                    ->searchable()
                    ->nullable(),
            ]),

            Textarea::make('description')->rows(3)->columnSpanFull(),

            Grid::make(3)->schema([
                Select::make('status')
                    ->options([
                        'active'   => 'Active',
                        'on-hold'  => 'On Hold',
                        'complete' => 'Complete ✅',
                        'archived' => 'Archived',
                    ])
                    ->default('active')
                    ->required(),

                TextInput::make('client_name')
                    ->label('Client Name')
                    ->placeholder('Leave blank for personal projects')
                    ->nullable(),

                DatePicker::make('due_date')->nullable(),
            ]),

            ColorPicker::make('color_hex')
                ->label('Project Colour')
                ->nullable(),
        ]),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            ColorColumn::make('color_hex')->label(''),
            TextColumn::make('name')->searchable()->weight('bold'),
            TextColumn::make('lifeArea.name')->label('Area')->badge()->sortable(),
            TextColumn::make('client_name')->label('Client')->placeholder('Personal')->color('gray'),
            TextColumn::make('status')->badge()
                ->color(fn (string $state): string => match($state) {
                    'active'   => 'success',
                    'on-hold'  => 'warning',
                    'complete' => 'info',
                    'archived' => 'gray',
                    default    => 'gray',
                }),
            TextColumn::make('due_date')->date('M j, Y')->sortable(),
        ])
        ->defaultSort('status')
        ->filters([
            SelectFilter::make('life_area_id')->label('Life Area')
                ->relationship('lifeArea', 'name'),
            SelectFilter::make('status')
                ->options([
                    'active' => 'Active', 'on-hold' => 'On Hold',
                    'complete' => 'Complete', 'archived' => 'Archived',
                ]),
        ])
        ->actions([EditAction::make(), DeleteAction::make()])
        ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
}

public static function getRelationManagers(): array
{
    return [
        \App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager::class,
    ];
}
```

---

## 6. TaskResource

Tasks are the atomic unit of work. This resource includes quick-complete actions and a rich filtering system.

```php
protected static ?string $model = Task::class;
protected static ?string $navigationIcon = 'heroicon-o-check-circle';
protected static ?string $navigationGroup = 'Goals & Projects';
protected static ?string $navigationLabel = 'Tasks';
protected static ?int $navigationSort = 4;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Task')->schema([
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('life_area_id')
                    ->label('Life Area')
                    ->options(LifeAreaBadge::getOptions())
                    ->required()
                    ->searchable(),

                Select::make('project_id')
                    ->label('Project (optional)')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->nullable(),
            ]),

            Grid::make(2)->schema([
                Select::make('goal_id')
                    ->label('Goal (optional)')
                    ->relationship('goal', 'title')
                    ->searchable()
                    ->nullable(),

                Select::make('milestone_id')
                    ->label('Milestone (optional)')
                    ->relationship('milestone', 'title')
                    ->searchable()
                    ->nullable(),
            ]),

            Textarea::make('notes')->rows(3)->columnSpanFull(),
        ]),

        Section::make('Scheduling & Priority')->schema([
            Grid::make(2)->schema([
                Select::make('status')
                    ->options([
                        'todo'        => 'To Do',
                        'in-progress' => 'In Progress',
                        'done'        => 'Done ✅',
                        'deferred'    => 'Deferred',
                    ])
                    ->default('todo')
                    ->required(),

                Select::make('priority')
                    ->options([
                        'low'      => 'Low',
                        'medium'   => 'Medium',
                        'high'     => 'High 🔥',
                        'critical' => 'Critical ⚡',
                    ])
                    ->default('medium')
                    ->required(),
            ]),

            Grid::make(3)->schema([
                DatePicker::make('scheduled_date')->label('Scheduled For'),
                DatePicker::make('due_date')->label('Due Date'),
                TextInput::make('time_estimate_minutes')
                    ->label('Estimate (mins)')
                    ->numeric()
                    ->nullable(),
            ]),

            Toggle::make('is_daily_action')
                ->label('Daily Action')
                ->helperText('Pin this task to today\'s Daily Plan')
                ->inline(false),
        ]),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')
                ->searchable()
                ->weight('bold')
                ->wrap()
                ->description(fn ($record) => $record->project?->name),

            TextColumn::make('lifeArea.name')
                ->label('Area')
                ->badge()
                ->sortable(),

            TextColumn::make('priority')
                ->badge()
                ->color(fn (string $state): string => match($state) {
                    'low'      => 'gray',
                    'medium'   => 'info',
                    'high'     => 'warning',
                    'critical' => 'danger',
                    default    => 'gray',
                }),

            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match($state) {
                    'todo'        => 'gray',
                    'in-progress' => 'warning',
                    'done'        => 'success',
                    'deferred'    => 'info',
                    default       => 'gray',
                }),

            TextColumn::make('scheduled_date')
                ->label('Scheduled')
                ->date('M j')
                ->sortable(),

            TextColumn::make('due_date')
                ->label('Due')
                ->date('M j')
                ->sortable()
                ->color(fn ($record) =>
                    $record->due_date?->isPast() && $record->status !== 'done'
                        ? 'danger'
                        : 'gray'
                ),

            IconColumn::make('is_daily_action')
                ->label('Daily')
                ->boolean()
                ->trueIcon('heroicon-o-sun')
                ->falseIcon('heroicon-o-minus'),
        ])
        ->defaultSort('due_date')
        ->filters([
            SelectFilter::make('life_area_id')
                ->label('Life Area')
                ->relationship('lifeArea', 'name'),

            SelectFilter::make('status')
                ->options([
                    'todo' => 'To Do', 'in-progress' => 'In Progress',
                    'done' => 'Done', 'deferred' => 'Deferred',
                ]),

            SelectFilter::make('priority')
                ->options([
                    'low' => 'Low', 'medium' => 'Medium',
                    'high' => 'High', 'critical' => 'Critical',
                ]),

            TernaryFilter::make('is_daily_action')->label('Daily Actions Only'),

            Filter::make('overdue')
                ->label('Overdue')
                ->query(fn (Builder $query) =>
                    $query->whereDate('due_date', '<', today())
                          ->whereNotIn('status', ['done'])
                ),
        ], layout: FiltersLayout::AboveContent)
        ->actions([
            \Filament\Tables\Actions\Action::make('complete')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->label('Complete')
                ->visible(fn ($record) => $record->status !== 'done')
                ->action(fn ($record) => $record->update(['status' => 'done'])),
            EditAction::make(),
            DeleteAction::make(),
        ])
        ->bulkActions([
            BulkActionGroup::make([
                \Filament\Tables\Actions\BulkAction::make('markDone')
                    ->label('Mark Done')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn ($records) => $records->each->update(['status' => 'done'])),
                DeleteBulkAction::make(),
            ]),
        ]);
}
```

---

## 7. HabitResource

Habits are the backbone of the daily rhythm. This resource manages the habit definitions; logging is handled by `HabitLogResource`.

```php
protected static ?string $model = Habit::class;
protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
protected static ?string $navigationGroup = 'Habits';
protected static ?string $navigationLabel = 'Habits';
protected static ?int $navigationSort = 1;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Habit Details')->schema([
            Grid::make(2)->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Morning pages, 30-min walk'),

                Select::make('life_area_id')
                    ->label('Life Area')
                    ->options(LifeAreaBadge::getOptions())
                    ->required()
                    ->searchable(),
            ]),

            Textarea::make('description')
                ->rows(2)
                ->columnSpanFull()
                ->placeholder('Optional context or instructions for this habit'),
        ]),

        Section::make('Schedule')->schema([
            Grid::make(2)->schema([
                Select::make('frequency')
                    ->options([
                        'daily'    => 'Every Day',
                        'weekdays' => 'Weekdays Only',
                        'weekly'   => 'Once a Week',
                        'custom'   => 'Custom Days',
                    ])
                    ->default('daily')
                    ->live()
                    ->required(),

                Select::make('time_of_day')
                    ->label('Best Time of Day')
                    ->options([
                        'morning'   => '🌅 Morning',
                        'afternoon' => '☀️ Afternoon',
                        'evening'   => '🌙 Evening',
                        'anytime'   => 'Anytime',
                    ])
                    ->default('anytime'),
            ]),

            \Filament\Forms\Components\CheckboxList::make('target_days')
                ->label('Active Days')
                ->options([
                    '0' => 'Sunday',
                    '1' => 'Monday',
                    '2' => 'Tuesday',
                    '3' => 'Wednesday',
                    '4' => 'Thursday',
                    '5' => 'Friday',
                    '6' => 'Saturday',
                ])
                ->columns(7)
                ->visible(fn ($get) => $get('frequency') === 'custom')
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('status')
                    ->options(['active' => 'Active', 'paused' => 'Paused'])
                    ->default('active')
                    ->required(),

                DatePicker::make('started_at')
                    ->label('Start Date')
                    ->default(today())
                    ->required(),
            ]),
        ]),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')->searchable()->weight('bold'),
            TextColumn::make('lifeArea.name')->label('Area')->badge()->sortable(),
            TextColumn::make('frequency')->badge()
                ->color(fn (string $state): string => match($state) {
                    'daily'    => 'success',
                    'weekdays' => 'info',
                    'weekly'   => 'warning',
                    'custom'   => 'gray',
                    default    => 'gray',
                }),
            TextColumn::make('time_of_day')->label('Time')->badge()->color('gray'),
            TextColumn::make('streak_current')
                ->label('🔥 Streak')
                ->sortable()
                ->weight('bold')
                ->color('warning'),
            TextColumn::make('streak_best')
                ->label('Best')
                ->sortable()
                ->color('gray'),
            TextColumn::make('status')->badge()
                ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
        ])
        ->defaultSort('streak_current', 'desc')
        ->filters([
            SelectFilter::make('status')
                ->options(['active' => 'Active', 'paused' => 'Paused']),
            SelectFilter::make('life_area_id')
                ->label('Life Area')
                ->relationship('lifeArea', 'name'),
        ])
        ->actions([
            \Filament\Tables\Actions\Action::make('log_today')
                ->label('Log Today')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) =>
                    $record->status === 'active' && !$record->todayLog
                )
                ->action(function ($record) {
                    \App\Models\HabitLog::create([
                        'habit_id'    => $record->id,
                        'logged_date' => today(),
                        'status'      => 'completed',
                    ]);
                    // Update streak via service
                    app(\App\Services\HabitStreakService::class)->recalculate($record);
                }),
            EditAction::make(),
            DeleteAction::make(),
        ]);
}
```

---

## 8. HabitLogResource

A log of every habit completion. Used for streak calculation, pattern analysis, and the monthly heatmap.

```php
protected static ?string $model = HabitLog::class;
protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
protected static ?string $navigationGroup = 'Habits';
protected static ?string $navigationLabel = 'Habit Logs';
protected static ?int $navigationSort = 2;

public static function form(Form $form): Form
{
    return $form->schema([
        Select::make('habit_id')
            ->label('Habit')
            ->relationship('habit', 'title')
            ->required()
            ->searchable(),

        Grid::make(2)->schema([
            DatePicker::make('logged_date')
                ->required()
                ->default(today()),

            Select::make('status')
                ->options([
                    'completed' => 'Completed ✅',
                    'skipped'   => 'Skipped',
                    'missed'    => 'Missed',
                ])
                ->default('completed')
                ->required(),
        ]),

        TextInput::make('note')->nullable()->columnSpanFull(),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('habit.title')->label('Habit')->searchable()->sortable(),
            TextColumn::make('logged_date')->date('M j, Y')->sortable(),
            TextColumn::make('status')->badge()
                ->color(fn (string $state): string => match($state) {
                    'completed' => 'success',
                    'skipped'   => 'warning',
                    'missed'    => 'danger',
                    default     => 'gray',
                }),
            TextColumn::make('note')->limit(40)->color('gray'),
        ])
        ->defaultSort('logged_date', 'desc')
        ->filters([
            SelectFilter::make('status')
                ->options(['completed' => 'Completed', 'skipped' => 'Skipped', 'missed' => 'Missed']),
        ])
        ->actions([EditAction::make(), DeleteAction::make()]);
}
```

---

## 9. DailyPlanResource

The central daily workflow resource. Contains both morning and evening session forms.

```php
protected static ?string $model = DailyPlan::class;
protected static ?string $navigationIcon = 'heroicon-o-sun';
protected static ?string $navigationGroup = 'Today';
protected static ?string $navigationLabel = 'Daily Plans';
protected static ?int $navigationSort = 1;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('🌅 Morning Session')->schema([
            Grid::make(2)->schema([
                DatePicker::make('plan_date')
                    ->label('Date')
                    ->required()
                    ->default(today()),

                TextInput::make('day_theme')
                    ->label('Day Theme')
                    ->placeholder('e.g. Deep Focus, Family First, Ship It')
                    ->maxLength(100),
            ]),

            Textarea::make('morning_intention')
                ->label('Morning Intention')
                ->rows(3)
                ->placeholder('What is your intention for today?')
                ->columnSpanFull(),

            Section::make('Top 3 Priorities')->schema([
                Select::make('top_priority_1')
                    ->label('Priority 1')
                    ->relationship('priority1', 'title')
                    ->searchable()
                    ->nullable(),

                Select::make('top_priority_2')
                    ->label('Priority 2')
                    ->relationship('priority2', 'title')
                    ->searchable()
                    ->nullable(),

                Select::make('top_priority_3')
                    ->label('Priority 3')
                    ->relationship('priority3', 'title')
                    ->searchable()
                    ->nullable(),
            ])->columns(3),

            Placeholder::make('ai_morning_prompt')
                ->label('AI Morning Intention')
                ->content(fn ($record) => $record?->ai_morning_prompt ?? 'Not yet generated.')
                ->columnSpanFull(),
        ]),

        Section::make('🌙 Evening Session')->schema([
            Grid::make(3)->schema([
                Select::make('energy_rating')
                    ->label('Energy (1–5)')
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                    ->nullable(),

                Select::make('focus_rating')
                    ->label('Focus (1–5)')
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                    ->nullable(),

                Select::make('progress_rating')
                    ->label('Progress (1–5)')
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                    ->nullable(),
            ]),

            Textarea::make('evening_reflection')
                ->label('Evening Reflection')
                ->rows(4)
                ->placeholder('What went well? What was hard? What did you learn?')
                ->columnSpanFull(),

            Placeholder::make('ai_evening_summary')
                ->label('AI Evening Summary')
                ->content(fn ($record) => $record?->ai_evening_summary ?? 'Not yet generated.')
                ->columnSpanFull(),

            Select::make('status')
                ->options([
                    'draft'    => 'Draft',
                    'active'   => 'Active',
                    'reviewed' => 'Reviewed ✅',
                ])
                ->default('draft'),
        ]),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('plan_date')
                ->label('Date')
                ->date('l, M j, Y')
                ->sortable()
                ->weight('bold'),

            TextColumn::make('day_theme')
                ->label('Theme')
                ->placeholder('—')
                ->badge()
                ->color('primary'),

            TextColumn::make('energy_rating')->label('⚡ Energy')->alignCenter(),
            TextColumn::make('focus_rating')->label('🎯 Focus')->alignCenter(),
            TextColumn::make('progress_rating')->label('📈 Progress')->alignCenter(),

            TextColumn::make('status')->badge()
                ->color(fn (string $state): string => match($state) {
                    'draft'    => 'gray',
                    'active'   => 'warning',
                    'reviewed' => 'success',
                    default    => 'gray',
                }),
        ])
        ->defaultSort('plan_date', 'desc')
        ->actions([ViewAction::make(), EditAction::make()])
        ->bulkActions([]);
}

public static function getRelationManagers(): array
{
    return [
        \App\Filament\Resources\DailyPlanResource\RelationManagers\TimeBlocksRelationManager::class,
    ];
}

public static function getPages(): array
{
    return [
        'index'  => Pages\ListDailyPlans::route('/'),
        'create' => Pages\CreateDailyPlan::route('/create'),
        'view'   => Pages\ViewDailyPlan::route('/{record}'),
        'edit'   => Pages\EditDailyPlan::route('/{record}/edit'),
    ];
}
```

---

## 10. TimeBlockResource

Time blocks are managed as a relation on the Daily Plan. This standalone resource provides an alternative view.

```php
protected static ?string $model = TimeBlock::class;
protected static ?string $navigationIcon = 'heroicon-o-clock';
protected static ?string $navigationGroup = 'Today';
protected static ?string $navigationLabel = 'Time Blocks';
protected static ?int $navigationSort = 2;
protected static bool $shouldRegisterNavigation = false; // Accessed via DailyPlan only

public static function form(Form $form): Form
{
    return $form->schema([
        Select::make('daily_plan_id')
            ->label('Daily Plan')
            ->relationship('dailyPlan', 'plan_date')
            ->getOptionLabelFromRecordUsing(fn ($record) => $record->plan_date->format('M j, Y'))
            ->required(),

        TextInput::make('title')->required()->columnSpanFull(),

        Grid::make(2)->schema([
            Select::make('block_type')
                ->options([
                    'deep-work' => '🧠 Deep Work',
                    'admin'     => '📋 Admin',
                    'meeting'   => '🗣️ Meeting',
                    'personal'  => '🌿 Personal',
                    'buffer'    => '⏸️ Buffer',
                ])
                ->default('deep-work')
                ->required(),

            ColorPicker::make('color_hex')->label('Colour'),
        ]),

        Grid::make(2)->schema([
            TimePicker::make('start_time')->required()->seconds(false),
            TimePicker::make('end_time')->required()->seconds(false),
        ]),

        Grid::make(2)->schema([
            Select::make('task_id')
                ->label('Linked Task')
                ->relationship('task', 'title')
                ->searchable()
                ->nullable(),

            Select::make('project_id')
                ->label('Linked Project')
                ->relationship('project', 'name')
                ->searchable()
                ->nullable(),
        ]),

        Textarea::make('notes')->rows(2)->nullable()->columnSpanFull(),
    ]);
}
```

### TimeBlocksRelationManager

```bash
php artisan make:filament-relation-manager DailyPlanResource timeBlocks title
```

```php
public function table(Table $table): Table
{
    return $table
        ->reorderable('start_time')
        ->columns([
            TextColumn::make('start_time')->label('Start')->time('g:i A')->sortable(),
            TextColumn::make('end_time')->label('End')->time('g:i A'),
            TextColumn::make('title'),
            TextColumn::make('block_type')->badge()
                ->color(fn (string $state): string => match($state) {
                    'deep-work' => 'success',
                    'admin'     => 'gray',
                    'meeting'   => 'warning',
                    'personal'  => 'info',
                    'buffer'    => 'gray',
                    default     => 'gray',
                }),
        ])
        ->defaultSort('start_time')
        ->headerActions([\Filament\Tables\Actions\CreateAction::make()])
        ->actions([EditAction::make(), DeleteAction::make()]);
}
```

---

## 11. JournalEntryResource

Rich journal with markdown support, mood tracking, and tags.

```php
protected static ?string $model = JournalEntry::class;
protected static ?string $navigationIcon = 'heroicon-o-book-open';
protected static ?string $navigationGroup = 'Journal';
protected static ?string $navigationLabel = 'Journal';
protected static ?int $navigationSort = 1;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make()->schema([
            Grid::make(3)->schema([
                DatePicker::make('entry_date')
                    ->required()
                    ->default(today()),

                Select::make('entry_type')
                    ->options([
                        'morning' => '🌅 Morning',
                        'evening' => '🌙 Evening',
                        'weekly'  => '📅 Weekly',
                        'freeform' => '✏️ Freeform',
                    ])
                    ->default('freeform')
                    ->required(),

                Select::make('mood')
                    ->label('Mood')
                    ->options([
                        1 => '😞 1 — Rough',
                        2 => '😐 2 — Low',
                        3 => '🙂 3 — Okay',
                        4 => '😊 4 — Good',
                        5 => '🌟 5 — Great',
                    ])
                    ->nullable(),
            ]),

            MarkdownEditor::make('content')
                ->required()
                ->columnSpanFull()
                ->toolbarButtons([
                    'bold', 'italic', 'bulletList', 'orderedList',
                    'heading', 'blockquote', 'link',
                ]),

            \Filament\Forms\Components\TagsInput::make('tags')
                ->separator(',')
                ->nullable()
                ->columnSpanFull(),

            Placeholder::make('ai_insights')
                ->label('AI Insights')
                ->content(fn ($record) => $record?->ai_insights ?? 'No AI insights yet.')
                ->columnSpanFull(),
        ]),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('entry_date')
                ->label('Date')
                ->date('l, M j, Y')
                ->sortable()
                ->weight('bold'),

            TextColumn::make('entry_type')
                ->badge()
                ->color(fn (string $state): string => match($state) {
                    'morning'  => 'warning',
                    'evening'  => 'info',
                    'weekly'   => 'success',
                    'freeform' => 'gray',
                    default    => 'gray',
                }),

            TextColumn::make('mood')
                ->label('Mood')
                ->formatStateUsing(fn ($state) => match((int) $state) {
                    1 => '😞', 2 => '😐', 3 => '🙂', 4 => '😊', 5 => '🌟',
                    default => '—',
                })
                ->alignCenter(),

            TextColumn::make('content')
                ->limit(80)
                ->html()
                ->color('gray'),
        ])
        ->defaultSort('entry_date', 'desc')
        ->filters([
            SelectFilter::make('entry_type')
                ->options([
                    'morning' => 'Morning', 'evening' => 'Evening',
                    'weekly' => 'Weekly', 'freeform' => 'Freeform',
                ]),
        ])
        ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);
}
```

---

## 12. WeeklyReviewResource

Weekly reviews are anchored to Monday of each week and feed the AI weekly analysis.

```php
protected static ?string $model = WeeklyReview::class;
protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
protected static ?string $navigationGroup = 'Journal';
protected static ?string $navigationLabel = 'Weekly Reviews';
protected static ?int $navigationSort = 2;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Week of')->schema([
            DatePicker::make('week_start_date')
                ->label('Week Starting (Monday)')
                ->required()
                ->default(today()->startOfWeek()),
        ]),

        Section::make('Reflection')->schema([
            Textarea::make('wins')
                ->label('🏆 Wins')
                ->rows(4)
                ->placeholder('What went well this week? List at least three wins.')
                ->columnSpanFull(),

            Textarea::make('friction')
                ->label('🚧 Friction')
                ->rows(4)
                ->placeholder('What blocked you? What drained your energy?')
                ->columnSpanFull(),

            Textarea::make('next_week_focus')
                ->label('🎯 Next Week Focus')
                ->rows(3)
                ->placeholder('What is the most important thing to focus on next week?')
                ->columnSpanFull(),
        ]),

        Section::make('Life Area Scores')->schema([
            Grid::make(3)->schema(
                collect([
                    'creative' => '🎨 Creative',
                    'business' => '💼 Business',
                    'health'   => '💚 Health',
                    'family'   => '👨‍👩‍👧 Family',
                    'growth'   => '📚 Growth',
                    'finance'  => '💰 Finance',
                ])->map(fn ($label, $key) =>
                    Select::make("outcomes_met.{$key}")
                        ->label($label)
                        ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                        ->nullable()
                )->values()->toArray()
            ),

            Select::make('overall_score')
                ->label('Overall Week Score (1–5)')
                ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                ->nullable(),
        ]),

        Section::make('AI Analysis')->schema([
            Placeholder::make('ai_analysis')
                ->label('AI Weekly Analysis')
                ->content(fn ($record) => $record?->ai_analysis ?? 'Submit the review to generate AI analysis.')
                ->columnSpanFull(),
        ]),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('week_start_date')
                ->label('Week of')
                ->date('M j, Y')
                ->sortable()
                ->weight('bold'),

            TextColumn::make('overall_score')
                ->label('Score')
                ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', $state) : '—')
                ->alignCenter(),

            TextColumn::make('wins')->limit(60)->color('gray'),
            TextColumn::make('next_week_focus')->label('Next Focus')->limit(50)->color('gray'),
        ])
        ->defaultSort('week_start_date', 'desc')
        ->actions([ViewAction::make(), EditAction::make()]);
}
```

---

## 13. MeetingDoneItemResource

Done items delivered to clients within meetings. Tracks outcomes with quantified results, client quotes, and testimonial opportunities.

```php
protected static ?string $model = MeetingDoneItem::class;
protected static ?string $navigationIcon = 'heroicon-o-check-badge';
protected static ?string $navigationGroup = 'Goals & Projects';
protected static ?string $navigationLabel = 'Done & Delivered';
protected static ?int $navigationSort = 11;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Done Item')->schema([
            Select::make('client_meeting_id')
                ->label('Client Meeting')
                ->relationship('clientMeeting', 'title')
                ->required()
                ->searchable(),

            TextInput::make('title')
                ->label('What was delivered')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),
        ]),

        Section::make('Impact & Testimonial')->schema([
            Grid::make(2)->schema([
                TextInput::make('outcome_metric')
                    ->label('Quantified Result')
                    ->placeholder('e.g. 25% efficiency gain, 3 new clients')
                    ->nullable(),

                TextInput::make('value_delivered')
                    ->label('Value Delivered ($)')
                    ->numeric()
                    ->prefix('$')
                    ->nullable(),
            ]),

            Textarea::make('client_quote')
                ->label('Client Quote')
                ->rows(2)
                ->placeholder('Their words about the delivery')
                ->columnSpanFull(),

            Toggle::make('save_as_testimonial')
                ->label('Save as testimonial for reuse')
                ->inline(false),
        ]),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('clientMeeting.meeting_date')
                ->label('When')
                ->date('M j, Y')
                ->sortable(),

            TextColumn::make('title')
                ->label('What was delivered')
                ->searchable()
                ->weight('bold')
                ->wrap(),

            TextColumn::make('outcome_metric')
                ->label('Quantified Result')
                ->color('success'),

            TextColumn::make('client_quote')
                ->label('Their words')
                ->limit(50)
                ->color('gray'),

            IconColumn::make('save_as_testimonial')
                ->label('Testimonial')
                ->boolean()
                ->trueIcon('heroicon-o-star')
                ->falseIcon('heroicon-o-minus'),
        ])
        ->defaultSort('clientMeeting.meeting_date', 'desc')
        ->actions([EditAction::make(), DeleteAction::make()]);
}
```

---

## 14. AiInteractionResource

A read-only audit log of all AI interactions. No create/edit — all entries are generated by the system.

```php
protected static ?string $model = AiInteraction::class;
protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
protected static ?string $navigationGroup = 'AI Studio';
protected static ?string $navigationLabel = 'AI History';
protected static ?int $navigationSort = 2;
protected static bool $canCreate = false; // System-generated only

public static function form(Form $form): Form
{
    return $form->schema([
        Placeholder::make('interaction_type')->content(fn ($record) => $record->interaction_type),
        Placeholder::make('model_used')->content(fn ($record) => $record->model_used ?? '—'),
        Placeholder::make('tokens_used')->content(fn ($record) => $record->tokens_used ?? '—'),
        Placeholder::make('prompt')
            ->content(fn ($record) => $record->prompt)
            ->columnSpanFull(),
        Placeholder::make('response')
            ->content(fn ($record) => $record->response ?? 'Pending...')
            ->columnSpanFull(),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('created_at')
                ->label('When')
                ->dateTime('M j, g:i A')
                ->sortable(),

            TextColumn::make('interaction_type')
                ->label('Type')
                ->badge()
                ->color(fn (string $state): string => match($state) {
                    'daily-morning'  => 'warning',
                    'daily-evening'  => 'info',
                    'weekly'         => 'success',
                    'goal-breakdown' => 'danger',
                    'freeform'       => 'gray',
                    default          => 'gray',
                }),

            TextColumn::make('model_used')->label('Model')->color('gray'),
            TextColumn::make('tokens_used')->label('Tokens')->alignRight()->color('gray'),

            TextColumn::make('prompt')->limit(60)->color('gray'),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            SelectFilter::make('interaction_type')
                ->options([
                    'daily-morning'  => 'Morning',
                    'daily-evening'  => 'Evening',
                    'weekly'         => 'Weekly',
                    'goal-breakdown' => 'Goal Breakdown',
                    'freeform'       => 'Freeform',
                ]),
        ])
        ->actions([ViewAction::make()])
        ->bulkActions([]);
}

public static function canCreate(): bool
{
    return false;
}
```

---

## 15. Navigation Registration

Register all resources and navigation groups in `AdminPanelProvider`:

```php
// app/Providers/Filament/AdminPanelProvider.php

use Filament\Navigation\NavigationGroup;

->navigationGroups([
    NavigationGroup::make('Today')
        ->icon('heroicon-o-sun')
        ->collapsed(false),

    NavigationGroup::make('Goals & Projects')
        ->icon('heroicon-o-flag')
        ->collapsed(false),

    NavigationGroup::make('Habits')
        ->icon('heroicon-o-arrow-path')
        ->collapsed(false),

    NavigationGroup::make('Journal')
        ->icon('heroicon-o-book-open')
        ->collapsed(false),

    NavigationGroup::make('Progress')
        ->icon('heroicon-o-chart-bar')
        ->collapsed(true),

    NavigationGroup::make('AI Studio')
        ->icon('heroicon-o-cpu-chip')
        ->collapsed(true),

    NavigationGroup::make('Settings')
        ->icon('heroicon-o-cog-6-tooth')
        ->collapsed(true),
])
```

### Navigation Order Summary

| Group | Resource | Sort |
|-------|----------|------|
| Today | Daily Plans | 1 |
| Today | Time Blocks | 2 |
| Goals & Projects | Goals | 1 |
| Goals & Projects | Milestones | 2 |
| Goals & Projects | Projects | 3 |
| Goals & Projects | Tasks | 4 |
| Goals & Projects | Client Meetings | 5 |
| Goals & Projects | Meeting Agendas | 6 |
| Goals & Projects | Someday / Maybe | 7 |
| Goals & Projects | Opportunity Pipeline | 8 |
| Goals & Projects | Budgets | 9 |
| Goals & Projects | Time Entries | 10 |
| Goals & Projects | Done & Delivered | 11 |
| Habits | Habits | 1 |
| Habits | Habit Logs | 2 |
| Journal | Journal Entries | 1 |
| Journal | Weekly Reviews | 2 |
| Progress | *(Widgets — Phase 2)* | — |
| AI Studio | AI History | 2 |
| Settings | Life Areas | 1 |

---

*Solas Rún • Version 1.1 • Filament Resources*
