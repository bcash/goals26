<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectBudgetResource\Pages;
use App\Models\ProjectBudget;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectBudgetResource extends Resource
{
    protected static ?string $model = ProjectBudget::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Goals & Projects';

    protected static ?string $navigationLabel = 'Budgets';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
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
                            'fixed' => 'Fixed Price',
                            'hourly' => 'Hourly',
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
                        'fixed' => 'info',
                        'hourly' => 'warning',
                        'retainer' => 'success',
                        default => 'gray',
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
                    ->state(fn ($record) => $record->percentUsed().'%')
                    ->color(fn ($record) => match (true) {
                        $record->isOverBudget() => 'danger',
                        $record->isNearAlert() => 'warning',
                        default => 'success',
                    })
                    ->alignCenter(),

            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('budget_type')
                    ->options([
                        'fixed' => 'Fixed Price',
                        'hourly' => 'Hourly',
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
            'index' => Pages\ListProjectBudgets::route('/'),
            'create' => Pages\CreateProjectBudget::route('/create'),
            'edit' => Pages\EditProjectBudget::route('/{record}/edit'),
        ];
    }
}
