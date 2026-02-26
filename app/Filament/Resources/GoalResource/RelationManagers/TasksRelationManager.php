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

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'todo' => 'gray',
                        'in-progress' => 'warning',
                        'done' => 'success',
                        'deferred' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('priority')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('due_date')->date('M j, Y')->sortable(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->headerActions([CreateAction::make()]);
    }
}
