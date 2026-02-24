<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Filament\Support\LifeAreaBadge;
use App\Models\Project;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, Grid, TextInput, Textarea, Select, DatePicker, ColorPicker};
use Filament\Tables\Columns\{TextColumn, ColorColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\{EditAction, DeleteAction, ViewAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Goals & Projects';
    protected static ?string $navigationLabel = 'Projects';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Project Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    Select::make('life_area_id')
                        ->label('Life Area')
                        ->options(LifeAreaBadge::getOptions())
                        ->required()
                        ->searchable(),

                    Select::make('goal_id')
                        ->label('Linked Goal (optional)')
                        ->relationship('goal', 'title')
                        ->searchable()
                        ->nullable(),
                ]),

                Textarea::make('description')->rows(3)->columnSpanFull(),

                Grid::make(3)->schema([
                    Select::make('status')
                        ->options([
                            'active'   => 'Active',
                            'on-hold'  => 'On Hold',
                            'complete' => 'Complete',
                            'archived' => 'Archived',
                        ])
                        ->default('active')
                        ->required(),

                    TextInput::make('client_name')
                        ->label('Client Name')
                        ->placeholder('Leave blank for personal projects')
                        ->nullable(),

                    DatePicker::make('due_date')->nullable(),
                ]),

                ColorPicker::make('color_hex')
                    ->label('Project Colour')
                    ->nullable(),
            ]),

            Section::make('Project Budget')
                ->description('Budget tracking using integer cents storage')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('budget_cents')
                            ->label('Budget ($)')
                            ->prefix('$')
                            ->numeric()
                            ->step(0.01)
                            ->nullable()
                            ->formatStateUsing(function ($state) {
                                if ($state instanceof \Money\Money) {
                                    return number_format((int) $state->getAmount() / 100, 2, '.', '');
                                }

                                return $state !== null ? number_format($state / 100, 2, '.', '') : null;
                            })
                            ->dehydrateStateUsing(fn ($state) => $state !== null
                                ? (int) round((float) $state * 100)
                                : null),

                        Select::make('budget_currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                                'AUD' => 'AUD',
                            ])
                            ->default('USD'),
                    ]),
                ]),

            Section::make('VPO Account')
                ->description('Link this project to a VPO account for client data integration')
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('vpo_account_id')
                        ->label('VPO Account ID')
                        ->placeholder('Enter the VPO account identifier')
                        ->nullable()
                        ->maxLength(50),
                ]),

            Section::make('Specification & Export')
                ->description('Tech stack, architecture, and export template for spec generation')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('tech_stack')
                        ->label('Tech Stack')
                        ->placeholder('e.g., Laravel 12, React 19, PostgreSQL 15, Tailwind CSS 4')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('architecture_notes')
                        ->label('Architecture Notes')
                        ->placeholder('High-level architecture: patterns, data flow, deployment...')
                        ->rows(5)
                        ->columnSpanFull(),

                    Textarea::make('export_template')
                        ->label('Custom CLAUDE.md Template')
                        ->placeholder('Additional instructions to include in exported CLAUDE.md for the implementing team...')
                        ->rows(8)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color_hex')->label(''),
                TextColumn::make('name')->searchable()->weight('bold'),
                TextColumn::make('lifeArea.name')->label('Area')->badge()->sortable(),
                TextColumn::make('client_name')->label('Client')->placeholder('Personal')->color('gray'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'on-hold'  => 'warning',
                        'complete' => 'info',
                        'archived' => 'gray',
                        default    => 'gray',
                    }),
                TextColumn::make('due_date')->date('M j, Y')->sortable(),
            ])
            ->defaultSort('status')
            ->filters([
                SelectFilter::make('life_area_id')->label('Life Area')
                    ->relationship('lifeArea', 'name'),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active', 'on-hold' => 'On Hold',
                        'complete' => 'Complete', 'archived' => 'Archived',
                    ]),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\TasksRelationManager::class,
            RelationManagers\CostEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view'   => Pages\ViewProject::route('/{record}'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
