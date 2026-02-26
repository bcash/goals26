<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingAgendaResource\Pages;
use App\Filament\Resources\MeetingAgendaResource\RelationManagers;
use App\Models\MeetingAgenda;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MeetingAgendaResource extends Resource
{
    protected static ?string $model = MeetingAgenda::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Goals & Projects';

    protected static ?string $navigationLabel = 'Meeting Agendas';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Meeting Details')->schema([
                TextInput::make('title')->required()->columnSpanFull(),

                Grid::make(3)->schema([
                    Select::make('client_type')
                        ->label('Meeting With')
                        ->options([
                            'external' => 'External Client',
                            'self' => 'Myself',
                        ])
                        ->default('external')
                        ->live(),

                    TextInput::make('client_name')
                        ->label('Client Name')
                        ->nullable()
                        ->visible(fn ($get) => $get('client_type') === 'external'),

                    DateTimePicker::make('scheduled_for')
                        ->label('Scheduled For'),
                ]),

                Grid::make(2)->schema([
                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->nullable(),

                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'ready' => 'Ready to Send',
                            'in-progress' => 'In Progress',
                            'complete' => 'Complete',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('draft'),
                ]),

                Select::make('client_meeting_id')
                    ->label('Linked Meeting')
                    ->relationship('meeting', 'title')
                    ->searchable()
                    ->nullable(),

                Textarea::make('purpose')
                    ->label('Purpose of This Meeting')
                    ->placeholder('What do we need to accomplish in this meeting?')
                    ->rows(2)
                    ->columnSpanFull(),

                TagsInput::make('desired_outcomes')
                    ->label('Desired Outcomes')
                    ->placeholder('Add an outcome')
                    ->helperText('What does a successful meeting look like?')
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),
            ]),

            Section::make('AI Suggested Topics')->schema([
                Placeholder::make('ai_suggested_topics')
                    ->label('')
                    ->content(fn ($record) => $record?->ai_suggested_topics
                            ? collect($record->ai_suggested_topics)
                                ->map(fn ($t) => "* {$t['title']} ({$t['time_allocation_minutes']}min) - {$t['description']}")
                                ->join("\n")
                            : 'Save the agenda to generate AI topic suggestions.'
                    )
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->placeholder('No project'),

                TextColumn::make('scheduled_for')
                    ->label('Scheduled')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'ready' => 'info',
                        'in-progress' => 'warning',
                        'complete' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),
            ])
            ->defaultSort('scheduled_for', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft', 'ready' => 'Ready',
                        'in-progress' => 'In Progress', 'complete' => 'Complete',
                    ]),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\AgendaItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetingAgendas::route('/'),
            'create' => Pages\CreateMeetingAgenda::route('/create'),
            'view' => Pages\ViewMeetingAgenda::route('/{record}'),
            'edit' => Pages\EditMeetingAgenda::route('/{record}/edit'),
        ];
    }
}
