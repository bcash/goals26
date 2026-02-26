<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeeklyReviewResource\Pages;
use App\Models\WeeklyReview;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WeeklyReviewResource extends Resource
{
    protected static ?string $model = WeeklyReview::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Journal';

    protected static ?string $navigationLabel = 'Weekly Reviews';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Week of')->schema([
                DatePicker::make('week_start_date')
                    ->label('Week Starting (Monday)')
                    ->required()
                    ->default(today()->startOfWeek()),
            ]),

            Section::make('Reflection')->schema([
                Textarea::make('wins')
                    ->label('Wins')
                    ->rows(4)
                    ->placeholder('What went well this week? List at least three wins.')
                    ->columnSpanFull(),

                Textarea::make('friction')
                    ->label('Friction')
                    ->rows(4)
                    ->placeholder('What blocked you? What drained your energy?')
                    ->columnSpanFull(),

                Textarea::make('next_week_focus')
                    ->label('Next Week Focus')
                    ->rows(3)
                    ->placeholder('What is the most important thing to focus on next week?')
                    ->columnSpanFull(),
            ]),

            Section::make('Life Area Scores')->schema([
                Grid::make(3)->schema(
                    collect([
                        'creative' => 'Creative',
                        'business' => 'Business',
                        'health' => 'Health',
                        'family' => 'Family',
                        'growth' => 'Growth',
                        'finance' => 'Finance',
                    ])->map(fn ($label, $key) => Select::make("outcomes_met.{$key}")
                        ->label($label)
                        ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                        ->nullable()
                    )->values()->toArray()
                ),

                Select::make('overall_score')
                    ->label('Overall Week Score (1-5)')
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                    ->nullable(),
            ]),

            Section::make('AI Analysis')->schema([
                Placeholder::make('ai_analysis')
                    ->label('AI Weekly Analysis')
                    ->content(fn ($record) => $record?->ai_analysis ?? 'Submit the review to generate AI analysis.')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('week_start_date')
                    ->label('Week of')
                    ->date('M j, Y')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('overall_score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => $state ? str_repeat('*', $state) : '-')
                    ->alignCenter(),

                TextColumn::make('wins')->limit(60)->color('gray'),
                TextColumn::make('next_week_focus')->label('Next Focus')->limit(50)->color('gray'),
            ])
            ->defaultSort('week_start_date', 'desc')
            ->actions([ViewAction::make(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeeklyReviews::route('/'),
            'create' => Pages\CreateWeeklyReview::route('/create'),
            'view' => Pages\ViewWeeklyReview::route('/{record}'),
            'edit' => Pages\EditWeeklyReview::route('/{record}/edit'),
        ];
    }
}
