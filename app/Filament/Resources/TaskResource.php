<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\RelationManagers\CostEntriesRelationManager;
use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Support\LifeAreaBadge;
use App\Models\Task;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{
    Section, Grid, TextInput, Textarea, Select,
    DatePicker, Toggle
};
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Filters\{SelectFilter, Filter, TernaryFilter};
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Actions\{EditAction, DeleteAction, Action, BulkAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};
use Illuminate\Database\Eloquent\Builder;

class TaskResource extends Resource
{
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
                        ->searchable()
                        ->nullable(),

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

            Section::make('Specification')
                ->description('Requirements and acceptance criteria for spec export')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('acceptance_criteria')
                        ->label('Acceptance Criteria')
                        ->placeholder("What does 'done' look like? List measurable outcomes...")
                        ->rows(5)
                        ->columnSpanFull(),

                    Textarea::make('technical_requirements')
                        ->label('Technical Requirements')
                        ->placeholder('Tech constraints, libraries, patterns, API contracts...')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('dependencies_description')
                        ->label('Dependencies')
                        ->placeholder('What must be completed first? External blockers...')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('AI Memory')
                ->description('Implementation plan and working context persisted across AI sessions')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('plan')
                        ->label('Implementation Plan')
                        ->placeholder('Approach, key files, implementation steps, decisions made...')
                        ->rows(5)
                        ->columnSpanFull(),

                    Textarea::make('context')
                        ->label('Working Context')
                        ->placeholder('Key files, specifications, requirements, and decisions...')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),

            Section::make('Scheduling & Priority')->schema([
                Grid::make(2)->schema([
                    Select::make('status')
                        ->options([
                            'todo'        => 'To Do',
                            'in-progress' => 'In Progress',
                            'done'        => 'Done',
                            'deferred'    => 'Deferred',
                        ])
                        ->default('todo')
                        ->required(),

                    Select::make('priority')
                        ->options([
                            'low'      => 'Low',
                            'medium'   => 'Medium',
                            'high'     => 'High',
                            'critical' => 'Critical',
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
                    ->color(fn (string $state): string => match ($state) {
                        'low'      => 'gray',
                        'medium'   => 'info',
                        'high'     => 'warning',
                        'critical' => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
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

                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name'),

                SelectFilter::make('goal_id')
                    ->label('Goal')
                    ->relationship('goal', 'title'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Action::make('complete')
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
                    BulkAction::make('markDone')
                        ->label('Mark Done')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['status' => 'done'])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            CostEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit'   => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
