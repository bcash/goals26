<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeEntryResource\Pages;
use App\Models\TimeEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Goals & Projects';

    protected static ?string $navigationLabel = 'Time Entries';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Time Entry')->schema([
                Grid::make(2)->schema([
                    Select::make('task_id')
                        ->label('Task')
                        ->relationship('task', 'title')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                ]),

                TextInput::make('description')
                    ->label('Description')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Grid::make(3)->schema([
                    TextInput::make('hours')
                        ->label('Hours')
                        ->numeric()
                        ->required()
                        ->step(0.25)
                        ->minValue(0.01),

                    TextInput::make('hourly_rate')
                        ->label('Hourly Rate')
                        ->numeric()
                        ->prefix('$')
                        ->nullable(),

                    DatePicker::make('logged_date')
                        ->label('Logged Date')
                        ->required()
                        ->default(today()),
                ]),

                Toggle::make('billable')
                    ->label('Billable')
                    ->default(true)
                    ->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('task.title')
                    ->label('Task')
                    ->limit(30)
                    ->placeholder('--')
                    ->sortable(),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->limit(25)
                    ->placeholder('--')
                    ->sortable(),

                TextColumn::make('hours')
                    ->label('Hours')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('billable')
                    ->label('Billable')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus')
                    ->alignCenter(),

                TextColumn::make('cost')
                    ->label('Cost')
                    ->money('USD')
                    ->sortable()
                    ->state(fn ($record) => ($record->hours ?? 0) * ($record->hourly_rate ?? 0)),

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
            'index' => Pages\ListTimeEntries::route('/'),
            'create' => Pages\CreateTimeEntry::route('/create'),
            'edit' => Pages\EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
