<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeBlockResource\Pages;
use App\Models\TimeBlock;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{Grid, TextInput, Textarea, Select, TimePicker, ColorPicker};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\{EditAction, DeleteAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};

class TimeBlockResource extends Resource
{
    protected static ?string $model = TimeBlock::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Today';
    protected static ?string $navigationLabel = 'Time Blocks';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('daily_plan_id')
                ->label('Daily Plan')
                ->relationship('dailyPlan', 'plan_date')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->plan_date->format('M j, Y'))
                ->required(),

            TextInput::make('title')->required()->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('block_type')
                    ->options([
                        'deep-work' => 'Deep Work',
                        'admin'     => 'Admin',
                        'meeting'   => 'Meeting',
                        'personal'  => 'Personal',
                        'buffer'    => 'Buffer',
                    ])
                    ->default('deep-work')
                    ->required(),

                ColorPicker::make('color_hex')->label('Colour'),
            ]),

            Grid::make(2)->schema([
                TimePicker::make('start_time')->required()->seconds(false),
                TimePicker::make('end_time')->required()->seconds(false),
            ]),

            Grid::make(2)->schema([
                Select::make('task_id')
                    ->label('Linked Task')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->nullable(),

                Select::make('project_id')
                    ->label('Linked Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->nullable(),
            ]),

            Textarea::make('notes')->rows(2)->nullable()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dailyPlan.plan_date')->label('Date')->date('M j, Y')->sortable(),
                TextColumn::make('start_time')->label('Start')->time('g:i A')->sortable(),
                TextColumn::make('end_time')->label('End')->time('g:i A'),
                TextColumn::make('title')->searchable(),
                TextColumn::make('block_type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deep-work' => 'success',
                        'admin'     => 'gray',
                        'meeting'   => 'warning',
                        'personal'  => 'info',
                        'buffer'    => 'gray',
                        default     => 'gray',
                    }),
                TextColumn::make('task.title')->label('Task')->limit(30)->placeholder('-'),
            ])
            ->defaultSort('start_time')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTimeBlocks::route('/'),
            'create' => Pages\CreateTimeBlock::route('/create'),
            'edit'   => Pages\EditTimeBlock::route('/{record}/edit'),
        ];
    }
}
