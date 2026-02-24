<?php

namespace App\Filament\Resources\ClientMeetingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Grid, Select, Textarea, Toggle};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Actions\{EditAction, DeleteAction, CreateAction};

class ScopeItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'scopeItems';

    public function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('description')
                ->required()
                ->rows(2)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('type')
                    ->options([
                        'in-scope'     => 'In Scope',
                        'out-of-scope' => 'Out of Scope',
                        'deferred'     => 'Deferred',
                        'assumption'   => 'Assumption',
                        'risk'         => 'Risk',
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
                    'in-scope'     => 'success',
                    'out-of-scope' => 'danger',
                    'deferred'     => 'warning',
                    'assumption'   => 'info',
                    'risk'         => 'danger',
                    default        => 'gray',
                }),
            TextColumn::make('description')->wrap()->limit(80),
            IconColumn::make('confirmed_with_client')->boolean()->label('Confirmed'),
            TextColumn::make('task.title')->label('Linked Task')->placeholder('-')->limit(30),
        ])
        ->headerActions([CreateAction::make()])
        ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
