<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoalResource\Pages;
use App\Filament\Resources\GoalResource\RelationManagers;
use App\Filament\Support\LifeAreaBadge;
use App\Models\Goal;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{
    Section, Grid, TextInput, Textarea, Select,
    DatePicker, Placeholder
};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\{EditAction, DeleteAction, ViewAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};

class GoalResource extends Resource
{
    protected static ?string $model = Goal::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Goals & Projects';
    protected static ?string $navigationLabel = 'Goals';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Goal')->schema([
                Grid::make(2)->schema([
                    Select::make('life_area_id')
                        ->label('Life Area')
                        ->options(LifeAreaBadge::getOptions())
                        ->required()
                        ->searchable(),

                    Select::make('horizon')
                        ->options([
                            '90-day'   => '90 Days',
                            '1-year'   => '1 Year',
                            '3-year'   => '3 Years',
                            'lifetime' => 'Lifetime',
                        ])
                        ->required()
                        ->default('1-year'),
                ]),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('What do you want to achieve?')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(3)
                    ->placeholder('Describe the goal in more detail...')
                    ->columnSpanFull(),

                Textarea::make('why')
                    ->label('Why does this matter?')
                    ->rows(3)
                    ->helperText('Your motivation. This is shown during your daily planning session.')
                    ->placeholder('Because...')
                    ->columnSpanFull(),
            ]),

            Section::make('Status & Progress')->schema([
                Grid::make(3)->schema([
                    Select::make('status')
                        ->options([
                            'active'    => 'Active',
                            'paused'    => 'Paused',
                            'achieved'  => 'Achieved',
                            'abandoned' => 'Abandoned',
                        ])
                        ->default('active')
                        ->required(),

                    DatePicker::make('target_date')
                        ->label('Target Date')
                        ->nullable(),

                    TextInput::make('progress_percent')
                        ->label('Progress %')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(0),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lifeArea.name')
                    ->label('Area')
                    ->badge()
                    ->color(fn ($record) => 'primary')
                    ->sortable(),

                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('horizon')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '90-day'   => 'warning',
                        '1-year'   => 'info',
                        '3-year'   => 'success',
                        'lifetime' => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'    => 'success',
                        'paused'    => 'warning',
                        'achieved'  => 'info',
                        'abandoned' => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('target_date')
                    ->label('Target')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn ($record) =>
                        $record->target_date?->isPast() && $record->status === 'active'
                            ? 'danger'
                            : 'gray'
                    ),
            ])
            ->defaultSort('status')
            ->filters([
                SelectFilter::make('life_area_id')
                    ->label('Life Area')
                    ->relationship('lifeArea', 'name'),

                SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active',
                        'paused'    => 'Paused',
                        'achieved'  => 'Achieved',
                        'abandoned' => 'Abandoned',
                    ]),

                SelectFilter::make('horizon')
                    ->options([
                        '90-day'   => '90 Days',
                        '1-year'   => '1 Year',
                        '3-year'   => '3 Years',
                        'lifetime' => 'Lifetime',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\MilestonesRelationManager::class,
            RelationManagers\TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGoals::route('/'),
            'create' => Pages\CreateGoal::route('/create'),
            'view'   => Pages\ViewGoal::route('/{record}'),
            'edit'   => Pages\EditGoal::route('/{record}/edit'),
        ];
    }
}
