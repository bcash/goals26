<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OpportunityPipelineResource\Pages;
use App\Models\OpportunityPipeline;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OpportunityPipelineResource extends Resource
{
    protected static ?string $model = OpportunityPipeline::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Goals & Projects';

    protected static ?string $navigationLabel = 'Opportunity Pipeline';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Opportunity')->schema([
                Grid::make(2)->schema([
                    Select::make('deferred_item_id')
                        ->label('Deferred Item')
                        ->relationship('deferredItem', 'title')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('stage')
                        ->options([
                            'identified' => 'Identified',
                            'qualifying' => 'Qualifying',
                            'nurturing' => 'Nurturing',
                            'proposing' => 'Proposing',
                            'negotiating' => 'Negotiating',
                            'closed-won' => 'Closed Won',
                            'closed-lost' => 'Closed Lost',
                        ])
                        ->required()
                        ->default('identified')
                        ->live(),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('probability_percent')
                        ->label('Probability %')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(20),

                    TextInput::make('estimated_value')
                        ->label('Estimated Value')
                        ->numeric()
                        ->prefix('$'),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('actual_value')
                        ->label('Actual Value')
                        ->numeric()
                        ->prefix('$')
                        ->visible(fn ($get) => in_array($get('stage'), ['closed-won', 'closed-lost'])),

                    Placeholder::make('weighted_value_display')
                        ->label('Weighted Value')
                        ->content(fn ($record) => $record
                            ? '$'.number_format($record->weightedValue(), 2)
                            : '--'
                        ),
                ]),
            ]),

            Section::make('Next Action')->schema([
                Grid::make(2)->schema([
                    TextInput::make('next_action')
                        ->label('Next Action')
                        ->maxLength(255),

                    DatePicker::make('next_action_date')
                        ->label('Next Action Date'),
                ]),
            ]),

            Section::make('Details')->schema([
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),

                DatePicker::make('actual_close_date')
                    ->label('Closed At')
                    ->visible(fn ($get) => in_array($get('stage'), ['closed-won', 'closed-lost'])),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deferredItem.title')
                    ->label('Deferred Item')
                    ->searchable()
                    ->weight('bold')
                    ->wrap()
                    ->limit(50),

                TextColumn::make('stage')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'identified' => 'gray',
                        'qualifying' => 'info',
                        'nurturing' => 'info',
                        'proposing' => 'warning',
                        'negotiating' => 'warning',
                        'closed-won' => 'success',
                        'closed-lost' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('probability_percent')
                    ->label('Prob.')
                    ->suffix('%')
                    ->alignCenter()
                    ->state(fn ($record) => $record->probability_percent ?? $record->probability ?? 0),

                TextColumn::make('weighted_value')
                    ->label('Weighted Value')
                    ->money('USD')
                    ->state(fn ($record) => $record->weightedValue())
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw(
                            'COALESCE(estimated_value, 0) * COALESCE(probability_percent, 0) / 100 '.$direction
                        );
                    }),

                TextColumn::make('next_action_date')
                    ->label('Next Action')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn ($record) => $record->next_action_date?->isPast() ? 'danger' : 'gray'
                    ),
            ])
            ->defaultSort('next_action_date')
            ->filters([
                SelectFilter::make('stage')
                    ->options([
                        'identified' => 'Identified',
                        'qualifying' => 'Qualifying',
                        'nurturing' => 'Nurturing',
                        'proposing' => 'Proposing',
                        'negotiating' => 'Negotiating',
                        'closed-won' => 'Closed Won',
                        'closed-lost' => 'Closed Lost',
                    ]),
            ])
            ->actions([
                Action::make('advance')
                    ->label('Advance Stage')
                    ->icon('heroicon-o-arrow-right')
                    ->color('success')
                    ->visible(fn ($record) => ! in_array($record->stage, ['closed-won', 'closed-lost'])
                    )
                    ->action(function ($record) {
                        $stages = [
                            'identified',
                            'qualifying',
                            'nurturing',
                            'proposing',
                            'negotiating',
                            'closed-won',
                        ];

                        $currentIndex = array_search($record->stage, $stages);
                        if ($currentIndex !== false && isset($stages[$currentIndex + 1])) {
                            $record->update(['stage' => $stages[$currentIndex + 1]]);
                        }
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunityPipelines::route('/'),
            'create' => Pages\CreateOpportunityPipeline::route('/create'),
            'view' => Pages\ViewOpportunityPipeline::route('/{record}'),
            'edit' => Pages\EditOpportunityPipeline::route('/{record}/edit'),
        ];
    }
}
