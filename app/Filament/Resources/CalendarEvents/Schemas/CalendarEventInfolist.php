<?php

namespace App\Filament\Resources\CalendarEvents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CalendarEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event Details')->schema([
                TextEntry::make('title'),

                TextEntry::make('description')
                    ->placeholder('No description')
                    ->columnSpanFull(),

                Grid::make(3)->schema([
                    TextEntry::make('start_at')
                        ->label('Start')
                        ->dateTime('M j, Y g:i A'),

                    TextEntry::make('end_at')
                        ->label('End')
                        ->dateTime('M j, Y g:i A'),

                    TextEntry::make('all_day')
                        ->label('All Day')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                ]),

                Grid::make(3)->schema([
                    TextEntry::make('event_type')
                        ->label('Type')
                        ->badge(),

                    TextEntry::make('status')
                        ->badge(),

                    TextEntry::make('source')
                        ->badge()
                        ->placeholder('manual'),
                ]),
            ]),

            Section::make('Context')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('lifeArea.name')
                        ->label('Life Area')
                        ->placeholder('None'),

                    TextEntry::make('project.name')
                        ->label('Project')
                        ->placeholder('None'),
                ]),

                TextEntry::make('location')
                    ->placeholder('No location'),

                TextEntry::make('attendees')
                    ->formatStateUsing(fn ($state): string => is_array($state)
                        ? collect($state)->pluck('email')->implode(', ')
                        : 'None'
                    )
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
