<?php

namespace App\Filament\Resources\CalendarEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CalendarEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event Details')->schema([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    DateTimePicker::make('start_at')
                        ->label('Start')
                        ->required(),

                    DateTimePicker::make('end_at')
                        ->label('End')
                        ->required(),
                ]),

                Grid::make(3)->schema([
                    Toggle::make('all_day')
                        ->label('All Day Event')
                        ->default(false),

                    Select::make('event_type')
                        ->options([
                            'meeting' => 'Meeting',
                            'rehearsal' => 'Rehearsal',
                            'personal' => 'Personal',
                            'focus' => 'Focus',
                            'other' => 'Other',
                        ])
                        ->default('meeting')
                        ->required(),

                    Select::make('status')
                        ->options([
                            'confirmed' => 'Confirmed',
                            'tentative' => 'Tentative',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('confirmed')
                        ->required(),
                ]),
            ]),

            Section::make('Context')->schema([
                Grid::make(2)->schema([
                    Select::make('life_area_id')
                        ->label('Life Area')
                        ->relationship('lifeArea', 'name')
                        ->searchable()
                        ->nullable(),

                    Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->nullable(),
                ]),

                TextInput::make('location')
                    ->nullable()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TagsInput::make('attendees')
                    ->placeholder('Add an attendee')
                    ->columnSpanFull(),
            ]),

            Section::make('Sync Info')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('source')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('manual'),

                        TextInput::make('google_event_id')
                            ->label('Google Event ID')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                ])
                ->collapsed()
                ->collapsible(),
        ]);
    }
}
