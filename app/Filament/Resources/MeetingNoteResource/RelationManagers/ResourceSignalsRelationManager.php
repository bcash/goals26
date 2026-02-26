<?php

namespace App\Filament\Resources\MeetingNoteResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResourceSignalsRelationManager extends RelationManager
{
    protected static string $relationship = 'resourceSignals';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('resource_type')
                ->options([
                    'budget' => 'Budget',
                    'time' => 'Time',
                    'technology' => 'Technology',
                    'capability' => 'Capability',
                    'team' => 'Team',
                    'readiness' => 'Readiness',
                    'dependency' => 'Dependency',
                ])
                ->required(),

            Textarea::make('description')
                ->required()
                ->rows(2)
                ->columnSpanFull(),

            TextInput::make('client_quote')
                ->label('Client Quote')
                ->nullable()
                ->columnSpanFull(),

            Grid::make(2)->schema([
                TextInput::make('constraint_timeline')
                    ->label('When does this constraint lift?')
                    ->placeholder('e.g. After Q1, When budget resets')
                    ->nullable(),

                Toggle::make('creates_revisit_opportunity')
                    ->label('Creates revisit opportunity')
                    ->default(true)
                    ->inline(false),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('resource_type')
                ->label('Resource')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'budget' => 'warning',
                    'time' => 'info',
                    'technology' => 'gray',
                    'capability' => 'primary',
                    'team' => 'success',
                    'readiness' => 'warning',
                    'dependency' => 'danger',
                    default => 'gray',
                }),

            TextColumn::make('description')->wrap()->limit(80),
            TextColumn::make('constraint_timeline')->label('Lifts')->placeholder('-')->color('gray'),

            IconColumn::make('creates_revisit_opportunity')
                ->label('Revisit')
                ->boolean()
                ->trueIcon('heroicon-o-arrow-path')
                ->falseIcon('heroicon-o-minus'),
        ])
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
