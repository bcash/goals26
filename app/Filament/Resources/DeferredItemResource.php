<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeferredItemResource\Pages;
use App\Models\DeferredItem;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{
    Section, Grid, TextInput, Textarea, Select,
    DatePicker, Placeholder, Checkbox
};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\{SelectFilter, Filter};
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Actions\{EditAction, DeleteAction, ViewAction, Action, BulkAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};

class DeferredItemResource extends Resource
{
    protected static ?string $model = DeferredItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Goals & Projects';
    protected static ?string $navigationLabel = 'Someday / Maybe';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Deferred Item')->schema([
                TextInput::make('title')->required()->columnSpanFull(),

                Grid::make(2)->schema([
                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->nullable(),

                    TextInput::make('client_name')->nullable(),
                ]),

                Textarea::make('description')->rows(2)->columnSpanFull(),

                Textarea::make('client_context')
                    ->label('Client Context')
                    ->helperText('What did the client say? What was the situation when this was deferred?')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('client_quote')
                    ->label('Client Quote')
                    ->helperText('Their exact words, if you have them')
                    ->nullable()
                    ->columnSpanFull(),

                Textarea::make('why_it_matters')
                    ->label('Why It Still Matters')
                    ->rows(2)
                    ->placeholder('Why is this worth keeping? What value does it represent?')
                    ->columnSpanFull(),
            ]),

            Section::make('Classification & Timing')->schema([
                Grid::make(3)->schema([
                    Select::make('client_type')
                        ->label('Client Type')
                        ->options([
                            'external' => 'External Client',
                            'self'     => 'Personal Goal',
                        ])
                        ->default('external')
                        ->required()
                        ->live(),

                    Select::make('deferral_reason')
                        ->label('Why Deferred')
                        ->options([
                            'budget'            => 'Budget',
                            'timeline'          => 'Timeline',
                            'priority'          => 'Priority',
                            'client-not-ready'  => 'Client Not Ready',
                            'scope-control'     => 'Scope Control',
                            'awaiting-decision' => 'Awaiting Decision',
                            'technology'        => 'Technology',
                            'personal'          => 'Personal',
                        ])
                        ->required(),

                    Select::make('opportunity_type')
                        ->label('Opportunity Type')
                        ->options([
                            'phase-2'              => 'Phase 2',
                            'upsell'               => 'Upsell',
                            'upgrade'              => 'Upgrade',
                            'new-project'          => 'New Project',
                            'retainer'             => 'Retainer',
                            'product-feature'      => 'Product Feature',
                            'personal-goal'        => 'Personal Goal',
                            'personal-development' => 'Personal Development',
                            'none'                 => 'None',
                        ])
                        ->required()
                        ->live(),
                ]),

                Grid::make(3)->schema([
                    TextInput::make('estimated_value')
                        ->label('Estimated Value ($)')
                        ->numeric()
                        ->prefix('$')
                        ->visible(fn ($get) =>
                            !in_array($get('opportunity_type'), ['none', 'personal-goal'])
                        ),

                    DatePicker::make('revisit_date')
                        ->label('Revisit Date')
                        ->nullable(),

                    Select::make('status')
                        ->options([
                            'someday'   => 'Someday / Maybe',
                            'scheduled' => 'Scheduled',
                            'in-review' => 'In Review',
                            'promoted'  => 'In Pipeline',
                            'proposed'  => 'Proposed',
                            'won'       => 'Won',
                            'lost'      => 'Lost',
                            'archived'  => 'Archived',
                        ])
                        ->default('someday'),
                ]),

                TextInput::make('revisit_trigger')
                    ->label('Revisit Trigger')
                    ->helperText('What event should bring this back to the surface?')
                    ->placeholder('e.g. After current project launches, When Q1 budget opens')
                    ->nullable()
                    ->columnSpanFull(),

                TextInput::make('value_notes')
                    ->label('Value Notes')
                    ->placeholder('How did you arrive at this estimate?')
                    ->nullable()
                    ->columnSpanFull(),
            ]),

            Section::make('Personal Resources Required')
                ->visible(fn ($get) => $get('client_type') === 'self')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('resource_requirements.time')
                            ->label('Time Required (hours)')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('resource_requirements.money')
                            ->label('Money Required ($)')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('resource_requirements.capability')
                            ->label('Skill/Capability Needed')
                            ->placeholder('e.g. "JavaScript", "Video editing"')
                            ->nullable(),

                        TextInput::make('resource_requirements.technology')
                            ->label('Technology/Tool Needed')
                            ->placeholder('e.g. "Better camera", "Cloud storage"')
                            ->nullable(),

                        Select::make('resource_requirements.energy')
                            ->label('Energy Level Required')
                            ->options([
                                'low'     => 'Low',
                                'medium'  => 'Medium',
                                'high'    => 'High',
                                'maximum' => 'Maximum',
                            ])
                            ->nullable(),

                        TextInput::make('resource_requirements.dependency')
                            ->label('Dependent On')
                            ->placeholder('e.g. "Complete manuscript first"')
                            ->nullable(),
                    ]),

                    Checkbox::make('resource_check_done')
                        ->label('Resource constraints have been verified'),
                ]),

            Section::make('AI Opportunity Analysis')->schema([
                Placeholder::make('ai_opportunity_analysis')
                    ->label('')
                    ->content(fn ($record) =>
                        $record?->ai_opportunity_analysis
                        ?? 'Save this item to generate an AI opportunity analysis.'
                    )
                    ->columnSpanFull(),
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
                    ->description(fn ($record) => $record->client_name),

                TextColumn::make('opportunity_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'phase-2'         => 'success',
                        'upsell'          => 'warning',
                        'upgrade'         => 'info',
                        'new-project'     => 'danger',
                        'retainer'        => 'success',
                        'product-feature' => 'info',
                        'personal-goal'   => 'gray',
                        default           => 'gray',
                    }),

                TextColumn::make('deferral_reason')
                    ->label('Reason')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('estimated_value')
                    ->label('Value')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'someday'   => 'gray',
                        'scheduled' => 'info',
                        'in-review' => 'warning',
                        'promoted'  => 'success',
                        'proposed'  => 'warning',
                        'won'       => 'success',
                        'lost'      => 'danger',
                        'archived'  => 'gray',
                        default     => 'gray',
                    }),

                TextColumn::make('revisit_date')
                    ->label('Revisit')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn ($record) =>
                        $record->revisit_date?->isPast() && in_array($record->status, ['someday', 'scheduled'])
                            ? 'danger'
                            : 'gray'
                    ),

                TextColumn::make('review_count')
                    ->label('Reviews')
                    ->alignCenter()
                    ->color('gray'),
            ])
            ->defaultSort('estimated_value', 'desc')
            ->filters([
                SelectFilter::make('opportunity_type')
                    ->label('Opportunity Type')
                    ->options([
                        'phase-2'         => 'Phase 2',
                        'upsell'          => 'Upsell',
                        'upgrade'         => 'Upgrade',
                        'new-project'     => 'New Project',
                        'retainer'        => 'Retainer',
                        'product-feature' => 'Product Feature',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'someday'   => 'Someday / Maybe',
                        'scheduled' => 'Scheduled',
                        'promoted'  => 'In Pipeline',
                    ]),

                Filter::make('overdue')
                    ->label('Overdue for Review')
                    ->query(fn ($query) =>
                        $query->where('revisit_date', '<=', today())
                              ->whereIn('status', ['someday', 'scheduled'])
                    ),

                Filter::make('high_value')
                    ->label('High Value (> $5k)')
                    ->query(fn ($query) =>
                        $query->where('estimated_value', '>=', 5000)
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->visible(fn ($record) =>
                        ($record->revisit_date?->isPast() && in_array($record->status, ['someday', 'scheduled']))
                        || $record->status === 'someday'
                    )
                    ->form([
                        Select::make('outcome')
                            ->options([
                                'keep-someday' => 'Keep in Someday / Maybe',
                                'reschedule'   => 'Reschedule',
                                'promote'      => 'Move to Pipeline',
                                'propose'      => 'Ready to Propose',
                                'archive'      => 'Archive',
                            ])
                            ->required()
                            ->live(),
                        DatePicker::make('next_revisit_date')
                            ->visible(fn ($get) => $get('outcome') === 'reschedule'),
                        Textarea::make('notes')->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        if (class_exists(\App\Services\DeferralService::class)) {
                            app(\App\Services\DeferralService::class)->submitReview(
                                $record,
                                $data['outcome'],
                                $data['notes'] ?? '',
                                $data['next_revisit_date'] ?? null
                            );
                        }
                    }),

                Action::make('promote')
                    ->label('Add to Pipeline')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('success')
                    ->visible(fn ($record) =>
                        $record->opportunity_type !== 'none'
                        && !in_array($record->status, ['promoted', 'proposed', 'won', 'archived'])
                    )
                    ->action(fn ($record) => $record->promote()),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('archive')
                        ->label('Archive Selected')
                        ->icon('heroicon-o-archive-box')
                        ->action(fn ($records) =>
                            $records->each->update(['status' => 'archived'])
                        ),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDeferredItems::route('/'),
            'create' => Pages\CreateDeferredItem::route('/create'),
            'view'   => Pages\ViewDeferredItem::route('/{record}'),
            'edit'   => Pages\EditDeferredItem::route('/{record}/edit'),
        ];
    }
}
