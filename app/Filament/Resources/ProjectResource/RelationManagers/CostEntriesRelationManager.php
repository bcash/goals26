<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CostEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'costEntries';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('description')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Grid::make(3)->schema([
                Select::make('category')
                    ->options([
                        'labour' => 'Labour',
                        'compute' => 'Compute',
                        'infrastructure' => 'Infrastructure',
                        'license' => 'License',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->default('labour')
                    ->live(),

                TextInput::make('amount_cents')
                    ->label('Amount ($)')
                    ->prefix('$')
                    ->numeric()
                    ->step(0.01)
                    ->required()
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof \Money\Money) {
                            return number_format((int) $state->getAmount() / 100, 2, '.', '');
                        }

                        return $state !== null ? number_format($state / 100, 2, '.', '') : null;
                    })
                    ->dehydrateStateUsing(fn ($state) => $state !== null
                        ? (int) round((float) $state * 100)
                        : null),

                TextInput::make('duration_minutes')
                    ->label('Duration (min)')
                    ->numeric()
                    ->nullable()
                    ->suffix('min')
                    ->visible(fn ($get) => $get('category') === 'labour'),
            ]),

            Grid::make(2)->schema([
                DatePicker::make('logged_date')
                    ->required()
                    ->default(today()),

                Toggle::make('billable')
                    ->default(true)
                    ->inline(false),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'labour' => 'info',
                        'compute' => 'warning',
                        'infrastructure' => 'success',
                        'license' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof \Money\Money) {
                            return '$'.number_format((int) $state->getAmount() / 100, 2);
                        }

                        return $state !== null ? '$'.number_format($state / 100, 2) : '—';
                    })
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->placeholder('--')
                    ->alignCenter(),

                IconColumn::make('billable')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus')
                    ->alignCenter(),

                TextColumn::make('logged_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('logged_date', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'labour' => 'Labour',
                        'compute' => 'Compute',
                        'infrastructure' => 'Infrastructure',
                        'license' => 'License',
                        'other' => 'Other',
                    ]),

                TernaryFilter::make('billable')
                    ->label('Billable Only'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->headerActions([CreateAction::make()])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
