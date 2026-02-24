<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HabitResource\Pages;
use App\Filament\Support\LifeAreaBadge;
use App\Models\Habit;
use App\Models\HabitLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{
    Section, Grid, TextInput, Textarea, Select,
    DatePicker, CheckboxList
};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\{EditAction, DeleteAction, Action};

class HabitResource extends Resource
{
    protected static ?string $model = Habit::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Habits';
    protected static ?string $navigationLabel = 'Habits';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Habit Details')->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Morning pages, 30-min walk'),

                    Select::make('life_area_id')
                        ->label('Life Area')
                        ->options(LifeAreaBadge::getOptions())
                        ->required()
                        ->searchable(),
                ]),

                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull()
                    ->placeholder('Optional context or instructions for this habit'),
            ]),

            Section::make('Schedule')->schema([
                Grid::make(2)->schema([
                    Select::make('frequency')
                        ->options([
                            'daily'    => 'Every Day',
                            'weekdays' => 'Weekdays Only',
                            'weekly'   => 'Once a Week',
                            'custom'   => 'Custom Days',
                        ])
                        ->default('daily')
                        ->live()
                        ->required(),

                    Select::make('time_of_day')
                        ->label('Best Time of Day')
                        ->options([
                            'morning'   => 'Morning',
                            'afternoon' => 'Afternoon',
                            'evening'   => 'Evening',
                            'anytime'   => 'Anytime',
                        ])
                        ->default('anytime'),
                ]),

                CheckboxList::make('target_days')
                    ->label('Active Days')
                    ->options([
                        '0' => 'Sunday',
                        '1' => 'Monday',
                        '2' => 'Tuesday',
                        '3' => 'Wednesday',
                        '4' => 'Thursday',
                        '5' => 'Friday',
                        '6' => 'Saturday',
                    ])
                    ->columns(7)
                    ->visible(fn ($get) => $get('frequency') === 'custom')
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    Select::make('status')
                        ->options(['active' => 'Active', 'paused' => 'Paused'])
                        ->default('active')
                        ->required(),

                    DatePicker::make('started_at')
                        ->label('Start Date')
                        ->default(today())
                        ->required(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->weight('bold'),
                TextColumn::make('lifeArea.name')->label('Area')->badge()->sortable(),
                TextColumn::make('frequency')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'daily'    => 'success',
                        'weekdays' => 'info',
                        'weekly'   => 'warning',
                        'custom'   => 'gray',
                        default    => 'gray',
                    }),
                TextColumn::make('time_of_day')->label('Time')->badge()->color('gray'),
                TextColumn::make('streak_current')
                    ->label('Streak')
                    ->sortable()
                    ->weight('bold')
                    ->color('warning'),
                TextColumn::make('streak_best')
                    ->label('Best')
                    ->sortable()
                    ->color('gray'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->defaultSort('streak_current', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(['active' => 'Active', 'paused' => 'Paused']),
                SelectFilter::make('life_area_id')
                    ->label('Life Area')
                    ->relationship('lifeArea', 'name'),
            ])
            ->actions([
                Action::make('log_today')
                    ->label('Log Today')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) =>
                        $record->status === 'active' && !$record->todayLog
                    )
                    ->action(function ($record) {
                        HabitLog::create([
                            'habit_id'    => $record->id,
                            'logged_date' => today(),
                            'status'      => 'completed',
                        ]);
                        if (class_exists(\App\Services\HabitStreakService::class)) {
                            app(\App\Services\HabitStreakService::class)->recalculate($record);
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListHabits::route('/'),
            'create' => Pages\CreateHabit::route('/create'),
            'edit'   => Pages\EditHabit::route('/{record}/edit'),
        ];
    }
}
