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

class ScopeItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'scopeItems';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Textarea::make('description')
                ->required()
                ->rows(2)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('type')
                    ->options([
                        'in-scope' => 'In Scope',
                        'out-of-scope' => 'Out of Scope',
                        'deferred' => 'Deferred',
                        'assumption' => 'Assumption',
                        'risk' => 'Risk',
                    ])
                    ->required(),

                Select::make('task_id')
                    ->label('Linked Task')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->nullable(),
            ]),

            TextInput::make('client_quote')
                ->label('Direct Client Quote')
                ->helperText('Exact words from the transcript that support this decision')
                ->nullable()
                ->columnSpanFull(),

            Toggle::make('confirmed_with_client')->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('type')->badge()
                ->color(fn (string $state): string => match ($state) {
                    'in-scope' => 'success',
                    'out-of-scope' => 'danger',
                    'deferred' => 'warning',
                    'assumption' => 'info',
                    'risk' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('description')->wrap()->limit(80),
            IconColumn::make('confirmed_with_client')->boolean()->label('Confirmed'),
            TextColumn::make('task.title')->label('Linked Task')->placeholder('-')->limit(30),
        ])
            ->headerActions([CreateAction::make()])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
