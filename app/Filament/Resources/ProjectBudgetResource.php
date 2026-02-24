<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectBudgetResource\Pages;
use App\Models\ProjectBudget;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{
    Section, Grid, TextInput, Textarea, Select, Placeholder
};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\{EditAction, DeleteAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};

class ProjectBudgetResource extends Resource
{
    protected static ?string $model = ProjectBudget::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Goals & Projects';
    protected static ?string $navigationLabel = 'Budgets';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Budget Setup')->schema([
                Grid::make(2)->schema([
                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),

                    Select::make('budget_type')
                        ->options([
                            'fixed'    => 'Fixed Price',
                            'hourly'   => 'Hourly',
                            'retainer' => 'Retainer',
                        ])
                        ->live()
                        ->required()
                        ->default('fixed'),
                ]),

                Grid::make(3)->schema([
                    TextInput::make('budget_total')
                        ->label('Total Budget')
                        ->numeric()
                        ->prefix('$'),

                    TextInput::make('hourly_rate')
                        ->label('Hourly Rate')
                        ->numeric()
                        ->prefix('$')
                        ->visible(fn ($get) => $get('budget_type') === 'hourly'),

                    TextInput::make('estimated_hours')
                        ->label('Estimated Hours')
                        ->numeric()
                        ->suffix('hrs'),
                ]),
            ]),

            Section::make('Tracking')->schema([
                Grid::make(3)->schema([
                    TextInput::make('actual_spend')
                        ->label('Actual Spend')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->default(0),

                    TextInput::make('estimated_remaining')
                        ->label('Estimated Remaining')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->default(0),

                    TextInput::make('burn_rate')
                        ->label('Burn Rate ($/day)')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->default(0),
                ]),

                TextInput::make('alert_threshold_percent')
                    ->label('Alert When Budget Reaches (%)')
                    ->numeric()
                    ->default(80)
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100),
            ]),

            Section::make('Notes')->schema([
                Textarea::make('notes')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('budget_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed'    => 'info',
                        'hourly'   => 'warning',
                        'retainer' => 'success',
                        default    => 'gray',
                    }),

                TextColumn::make('budget_total')
                    ->label('Budget')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('actual_spend')
                    ->label('Spent')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('burn_rate_percent')
                    ->label('Burn %')
                    ->state(fn ($record) => $record->percentUsed() . '%')
                    ->color(fn ($record) => match (true) {
                        $record->isOverBudget()  => 'danger',
                        $record->isNearAlert()   => 'warning',
                        default                  => 'success',
                    })
                    ->alignCenter(),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('budget_type')
                    ->options([
                        'fixed'    => 'Fixed Price',
                        'hourly'   => 'Hourly',
                        'retainer' => 'Retainer',
                    ]),

            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjectBudgets::route('/'),
            'create' => Pages\CreateProjectBudget::route('/create'),
            'edit'   => Pages\EditProjectBudget::route('/{record}/edit'),
        ];
    }
}
