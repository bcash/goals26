<?php

namespace App\Filament\Resources\MeetingAgendaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Grid, Select, Textarea};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\{EditAction, DeleteAction, CreateAction};

class AgendaItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required()->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('item_type')
                    ->options([
                        'topic'           => 'Topic',
                        'action-followup' => 'Action Follow-up',
                        'deferred-review' => 'Deferred Review',
                        'decision'        => 'Decision',
                        'new-business'    => 'New Business',
                        'budget-check'    => 'Budget Check',
                    ])
                    ->default('topic')
                    ->required(),

                TextInput::make('time_allocation_minutes')
                    ->label('Time (mins)')
                    ->numeric()
                    ->nullable(),
            ]),

            Textarea::make('description')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'discussed' => 'Discussed',
                        'deferred'  => 'Deferred',
                        'resolved'  => 'Resolved',
                        'skipped'   => 'Skipped',
                    ])
                    ->default('pending'),

                TextInput::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->default(0),
            ]),

            Textarea::make('outcome_notes')
                ->label('Outcome Notes')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('item_type')->label('Type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'topic'           => 'primary',
                        'action-followup' => 'warning',
                        'deferred-review' => 'info',
                        'decision'        => 'success',
                        'new-business'    => 'gray',
                        'budget-check'    => 'danger',
                        default           => 'gray',
                    }),
                TextColumn::make('time_allocation_minutes')->label('Mins')->alignCenter(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'gray',
                        'discussed' => 'info',
                        'deferred'  => 'warning',
                        'resolved'  => 'success',
                        'skipped'   => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('sort_order')
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
