<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CostEntryResource\Pages;
use App\Models\CostEntry;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{
    Section, Grid, TextInput, Select, DatePicker, Toggle
};
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Filters\{SelectFilter, TernaryFilter};
use Filament\Tables\Actions\{EditAction, DeleteAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};

class CostEntryResource extends Resource
{
    protected static ?string $model = CostEntry::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Goals & Projects';
    protected static ?string $navigationLabel = 'Cost Entries';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Cost Entry')->schema([
                Grid::make(2)->schema([
                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->live(),

                    Select::make('task_id')
                        ->label('Task')
                        ->relationship(
                            'task',
                            'title',
                            fn ($query, $get) => $get('project_id')
                                ? $query->where('project_id', $get('project_id'))
                                : $query
                        )
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),

                TextInput::make('description')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Grid::make(3)->schema([
                    Select::make('category')
                        ->options([
                            'labour'         => 'Labour',
                            'compute'        => 'Compute',
                            'infrastructure' => 'Infrastructure',
                            'license'        => 'License',
                            'other'          => 'Other',
                        ])
                        ->required()
                        ->default('labour')
                        ->live(),

                    TextInput::make('amount_cents')
                        ->label('Amount ($)')
                        ->prefix('$')
                        ->numeric()
                        ->step(0.01)
                        ->required()
                        ->formatStateUsing(function ($state) {
                            if ($state instanceof \Money\Money) {
                                return number_format((int) $state->getAmount() / 100, 2, '.', '');
                            }

                            return $state !== null ? number_format($state / 100, 2, '.', '') : null;
                        })
                        ->dehydrateStateUsing(fn ($state) => $state !== null
                            ? (int) round((float) $state * 100)
                            : null),

                    TextInput::make('duration_minutes')
                        ->label('Duration (minutes)')
                        ->numeric()
                        ->nullable()
                        ->suffix('min')
                        ->visible(fn ($get) => $get('category') === 'labour'),
                ]),

                Grid::make(2)->schema([
                    DatePicker::make('logged_date')
                        ->required()
                        ->default(today()),

                    Toggle::make('billable')
                        ->default(true)
                        ->inline(false),
                ]),
            ]),

            Section::make('Meeting Link')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Select::make('client_meeting_id')
                        ->label('Client Meeting')
                        ->relationship('clientMeeting', 'title')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->limit(25)
                    ->placeholder('--')
                    ->sortable(),

                TextColumn::make('task.title')
                    ->label('Task')
                    ->limit(25)
                    ->placeholder('--')
                    ->sortable(),

                TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'labour'         => 'info',
                        'compute'        => 'warning',
                        'infrastructure' => 'success',
                        'license'        => 'primary',
                        default          => 'gray',
                    }),

                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof \Money\Money) {
                            return '$' . number_format((int) $state->getAmount() / 100, 2);
                        }

                        return $state !== null ? '$' . number_format($state / 100, 2) : '—';
                    })
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->placeholder('--')
                    ->alignCenter(),

                IconColumn::make('billable')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus')
                    ->alignCenter(),

                TextColumn::make('logged_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('logged_date', 'desc')
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name'),

                SelectFilter::make('category')
                    ->options([
                        'labour'         => 'Labour',
                        'compute'        => 'Compute',
                        'infrastructure' => 'Infrastructure',
                        'license'        => 'License',
                        'other'          => 'Other',
                    ]),

                TernaryFilter::make('billable')
                    ->label('Billable Only'),
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
            'index'  => Pages\ListCostEntries::route('/'),
            'create' => Pages\CreateCostEntry::route('/create'),
            'edit'   => Pages\EditCostEntry::route('/{record}/edit'),
        ];
    }
}
