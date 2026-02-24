<?php

namespace App\Filament\Resources\DailyPlanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Grid, Select, Textarea, TimePicker, ColorPicker};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\{EditAction, DeleteAction, CreateAction};

class TimeBlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'timeBlocks';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->required()->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('block_type')
                    ->options([
                        'deep-work' => 'Deep Work',
                        'admin'     => 'Admin',
                        'meeting'   => 'Meeting',
                        'personal'  => 'Personal',
                        'buffer'    => 'Buffer',
                    ])
                    ->default('deep-work')
                    ->required(),

                ColorPicker::make('color_hex')->label('Colour'),
            ]),

            Grid::make(2)->schema([
                TimePicker::make('start_time')->required()->seconds(false),
                TimePicker::make('end_time')->required()->seconds(false),
            ]),

            Grid::make(2)->schema([
                Select::make('task_id')
                    ->label('Linked Task')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->nullable(),

                Select::make('project_id')
                    ->label('Linked Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->nullable(),
            ]),

            Textarea::make('notes')->rows(2)->nullable()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('start_time')
            ->columns([
                TextColumn::make('start_time')->label('Start')->time('g:i A')->sortable(),
                TextColumn::make('end_time')->label('End')->time('g:i A'),
                TextColumn::make('title'),
                TextColumn::make('block_type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deep-work' => 'success',
                        'admin'     => 'gray',
                        'meeting'   => 'warning',
                        'personal'  => 'info',
                        'buffer'    => 'gray',
                        default     => 'gray',
                    }),
            ])
            ->defaultSort('start_time')
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
