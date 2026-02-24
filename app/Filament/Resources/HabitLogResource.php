<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HabitLogResource\Pages;
use App\Models\HabitLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{Grid, TextInput, Select, DatePicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\{EditAction, DeleteAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};

class HabitLogResource extends Resource
{
    protected static ?string $model = HabitLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Habits';
    protected static ?string $navigationLabel = 'Habit Logs';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('habit_id')
                ->label('Habit')
                ->relationship('habit', 'title')
                ->required()
                ->searchable(),

            Grid::make(2)->schema([
                DatePicker::make('logged_date')
                    ->required()
                    ->default(today()),

                Select::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'skipped'   => 'Skipped',
                        'missed'    => 'Missed',
                    ])
                    ->default('completed')
                    ->required(),
            ]),

            TextInput::make('note')->nullable()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('habit.title')->label('Habit')->searchable()->sortable(),
                TextColumn::make('logged_date')->date('M j, Y')->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'skipped'   => 'warning',
                        'missed'    => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('note')->limit(40)->color('gray'),
            ])
            ->defaultSort('logged_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(['completed' => 'Completed', 'skipped' => 'Skipped', 'missed' => 'Missed']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListHabitLogs::route('/'),
            'create' => Pages\CreateHabitLog::route('/create'),
            'edit'   => Pages\EditHabitLog::route('/{record}/edit'),
        ];
    }
}
