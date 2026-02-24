<?php

namespace App\Filament\Resources\ClientMeetingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Grid, Textarea, Toggle};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Actions\{EditAction, DeleteAction, CreateAction};

class DoneItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'doneItems';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('What was delivered')
                ->required()
                ->columnSpanFull(),

            Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                TextInput::make('outcome_metric')
                    ->label('Quantified Result')
                    ->placeholder('e.g. 25% efficiency gain')
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
                ->columnSpanFull(),

            Toggle::make('save_as_testimonial')
                ->label('Save as testimonial')
                ->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->label('Delivered')
                ->searchable()
                ->weight('bold')
                ->wrap(),

            TextColumn::make('outcome_metric')
                ->label('Result')
                ->color('success'),

            TextColumn::make('client_quote')
                ->limit(40)
                ->color('gray'),

            IconColumn::make('save_as_testimonial')
                ->label('Testimonial')
                ->boolean()
                ->trueIcon('heroicon-o-star')
                ->falseIcon('heroicon-o-minus'),
        ])
        ->headerActions([CreateAction::make()])
        ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
