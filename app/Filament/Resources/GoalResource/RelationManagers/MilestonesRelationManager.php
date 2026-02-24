<?php

namespace App\Filament\Resources\GoalResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Grid, Select, DatePicker};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\{EditAction, DeleteAction, CreateAction};

class MilestonesRelationManager extends RelationManager
{
    protected static string $relationship = 'milestones';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required()->columnSpanFull(),
            Grid::make(2)->schema([
                DatePicker::make('due_date')->nullable(),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'complete' => 'Complete'])
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
            ->headerActions([CreateAction::make()]);
    }
}
