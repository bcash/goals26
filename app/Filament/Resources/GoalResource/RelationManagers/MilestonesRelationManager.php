<?php

namespace App\Filament\Resources\GoalResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MilestonesRelationManager extends RelationManager
{
    protected static string $relationship = 'milestones';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
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
