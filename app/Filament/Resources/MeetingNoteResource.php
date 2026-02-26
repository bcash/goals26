<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingNoteResource\Pages;
use App\Filament\Resources\MeetingNoteResource\RelationManagers;
use App\Models\MeetingNote;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MeetingNoteResource extends Resource
{
    protected static ?string $model = MeetingNote::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Goals & Projects';

    protected static ?string $navigationLabel = 'Meeting Notes';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make()->tabs([

                Tab::make('Meeting Details')->schema([
                    Grid::make(2)->schema([
                        Select::make('client_type')
                            ->label('Meeting With')
                            ->options([
                                'external' => 'External Client',
                                'self' => 'Myself (Internal)',
                            ])
                            ->default('external')
                            ->live()
                            ->required(),

                        Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->nullable(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('title')->required(),
                        DatePicker::make('meeting_date')->required()->default(today()),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('meeting_type')
                            ->options([
                                'discovery' => 'Discovery',
                                'requirements' => 'Requirements',
                                'check-in' => 'Check-in',
                                'brainstorm' => 'Brainstorm',
                                'review' => 'Review',
                                'planning' => 'Planning',
                                'retrospective' => 'Retrospective',
                                'handoff' => 'Handoff',
                            ])
                            ->default('check-in'),

                        Select::make('transcription_status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'complete' => 'Complete',
                                'failed' => 'Failed',
                            ])
                            ->disabled(),
                    ]),

                    TagsInput::make('attendees')
                        ->columnSpanFull(),

                    TextInput::make('source')
                        ->label('Source')
                        ->placeholder('granola or manual')
                        ->nullable(),

                    TextInput::make('granola_meeting_id')
                        ->label('Granola Meeting ID')
                        ->nullable(),
                ]),

                Tab::make('Transcript')->schema([
                    Textarea::make('transcript')
                        ->label('Transcript / Notes')
                        ->rows(15)
                        ->helperText('Synced from Granola automatically, or paste a transcript manually.')
                        ->columnSpanFull(),

                    Textarea::make('summary')->rows(4)->columnSpanFull(),
                    Textarea::make('decisions')->rows(3)->columnSpanFull(),
                ]),

                Tab::make('Scope & Actions')->schema([
                    Placeholder::make('ai_scope_analysis')
                        ->content(fn ($record) => $record?->ai_scope_analysis ?? 'Save with a transcript to generate scope analysis.'
                        )
                        ->columnSpanFull(),

                    Textarea::make('action_items')->rows(4)->columnSpanFull(),
                ]),

            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->searchable()
                    ->placeholder('No project'),

                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('meeting_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'discovery' => 'info',
                        'requirements' => 'warning',
                        'check-in' => 'success',
                        'brainstorm' => 'primary',
                        'review' => 'gray',
                        'planning' => 'warning',
                        'retrospective' => 'info',
                        'handoff' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('client_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'external' => 'primary',
                        'self' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('meeting_date')
                    ->date('M j, Y')
                    ->sortable(),

                TextColumn::make('source')
                    ->badge()
                    ->color('gray')
                    ->placeholder('manual'),

                TextColumn::make('transcription_status')
                    ->label('Analysis')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'complete' => 'success',
                        'processing' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('meeting_date', 'desc')
            ->filters([
                SelectFilter::make('meeting_type')
                    ->options([
                        'discovery' => 'Discovery', 'requirements' => 'Requirements',
                        'check-in' => 'Check-in', 'review' => 'Review',
                        'brainstorm' => 'Brainstorm', 'planning' => 'Planning',
                    ]),
                SelectFilter::make('client_type')
                    ->options(['external' => 'External', 'self' => 'Internal']),
                SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name'),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\ScopeItemsRelationManager::class,
            RelationManagers\DoneItemsRelationManager::class,
            RelationManagers\ResourceSignalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetingNotes::route('/'),
            'create' => Pages\CreateMeetingNote::route('/create'),
            'view' => Pages\ViewMeetingNote::route('/{record}'),
            'edit' => Pages\EditMeetingNote::route('/{record}/edit'),
        ];
    }
}
