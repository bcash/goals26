<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Grid, Select, DatePicker};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\{EditAction, DeleteAction, CreateAction};

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public function form(Form $form): Form
    {
        return $form->schema([
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
                        'todo'        => 'gray',
                        'in-progress' => 'warning',
                        'done'        => 'success',
                        'deferred'    => 'info',
                        default       => 'gray',
                    }),
                TextColumn::make('priority')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low'      => 'gray',
                        'medium'   => 'info',
                        'high'     => 'warning',
                        'critical' => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('due_date')->date('M j, Y')->sortable(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->headerActions([CreateAction::make()]);
    }
}
