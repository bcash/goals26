<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyPlanResource\Pages;
use App\Filament\Resources\DailyPlanResource\RelationManagers;
use App\Models\DailyPlan;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{
    Section, Grid, TextInput, Textarea, Select,
    DatePicker, Placeholder
};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\{EditAction, ViewAction};

class DailyPlanResource extends Resource
{
    protected static ?string $model = DailyPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-sun';
    protected static ?string $navigationGroup = 'Today';
    protected static ?string $navigationLabel = 'Daily Plans';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Morning Session')->schema([
                Grid::make(2)->schema([
                    DatePicker::make('plan_date')
                        ->label('Date')
                        ->required()
                        ->default(today()),

                    TextInput::make('day_theme')
                        ->label('Day Theme')
                        ->placeholder('e.g. Deep Focus, Family First, Ship It')
                        ->maxLength(100),
                ]),

                Textarea::make('morning_intention')
                    ->label('Morning Intention')
                    ->rows(3)
                    ->placeholder('What is your intention for today?')
                    ->columnSpanFull(),

                Section::make('Top 3 Priorities')->schema([
                    Select::make('top_priority_1')
                        ->label('Priority 1')
                        ->relationship('priority1', 'title')
                        ->searchable()
                        ->nullable(),

                    Select::make('top_priority_2')
                        ->label('Priority 2')
                        ->relationship('priority2', 'title')
                        ->searchable()
                        ->nullable(),

                    Select::make('top_priority_3')
                        ->label('Priority 3')
                        ->relationship('priority3', 'title')
                        ->searchable()
                        ->nullable(),
                ])->columns(3),

                Placeholder::make('ai_morning_prompt')
                    ->label('AI Morning Intention')
                    ->content(fn ($record) => $record?->ai_morning_prompt ?? 'Not yet generated.')
                    ->columnSpanFull(),
            ]),

            Section::make('Evening Session')->schema([
                Grid::make(3)->schema([
                    Select::make('energy_rating')
                        ->label('Energy (1-5)')
                        ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                        ->nullable(),

                    Select::make('focus_rating')
                        ->label('Focus (1-5)')
                        ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                        ->nullable(),

                    Select::make('progress_rating')
                        ->label('Progress (1-5)')
                        ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                        ->nullable(),
                ]),

                Textarea::make('evening_reflection')
                    ->label('Evening Reflection')
                    ->rows(4)
                    ->placeholder('What went well? What was hard? What did you learn?')
                    ->columnSpanFull(),

                Placeholder::make('ai_evening_summary')
                    ->label('AI Evening Summary')
                    ->content(fn ($record) => $record?->ai_evening_summary ?? 'Not yet generated.')
                    ->columnSpanFull(),

                Select::make('status')
                    ->options([
                        'draft'    => 'Draft',
                        'active'   => 'Active',
                        'reviewed' => 'Reviewed',
                    ])
                    ->default('draft'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan_date')
                    ->label('Date')
                    ->date('l, M j, Y')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('day_theme')
                    ->label('Theme')
                    ->placeholder('-')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('energy_rating')->label('Energy')->alignCenter(),
                TextColumn::make('focus_rating')->label('Focus')->alignCenter(),
                TextColumn::make('progress_rating')->label('Progress')->alignCenter(),

                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'    => 'gray',
                        'active'   => 'warning',
                        'reviewed' => 'success',
                        default    => 'gray',
                    }),
            ])
            ->defaultSort('plan_date', 'desc')
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\TimeBlocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDailyPlans::route('/'),
            'create' => Pages\CreateDailyPlan::route('/create'),
            'view'   => Pages\ViewDailyPlan::route('/{record}'),
            'edit'   => Pages\EditDailyPlan::route('/{record}/edit'),
        ];
    }
}
