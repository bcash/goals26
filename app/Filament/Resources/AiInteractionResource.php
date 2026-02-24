<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiInteractionResource\Pages;
use App\Models\AiInteraction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ViewAction;

class AiInteractionResource extends Resource
{
    protected static ?string $model = AiInteraction::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'AI Studio';
    protected static ?string $navigationLabel = 'AI History';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Placeholder::make('interaction_type')
                ->content(fn ($record) => $record->interaction_type),
            Placeholder::make('model_used')
                ->content(fn ($record) => $record->model_used ?? '-'),
            Placeholder::make('tokens_used')
                ->content(fn ($record) => $record->tokens_used ?? '-'),
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
                    ->color(fn (string $state): string => match ($state) {
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiInteractions::route('/'),
            'view'  => Pages\ViewAiInteraction::route('/{record}'),
        ];
    }
}
