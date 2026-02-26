<?php

namespace App\Filament\Resources\CalendarEvents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CalendarEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('start_at')
                    ->label('Starts')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'meeting' => 'primary',
                        'rehearsal' => 'warning',
                        'personal' => 'success',
                        'focus' => 'info',
                        'other' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('lifeArea.name')
                    ->label('Life Area')
                    ->placeholder('None')
                    ->toggleable(),

                TextColumn::make('attendees')
                    ->label('Attendees')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state) : '0')
                    ->toggleable(),

                TextColumn::make('source')
                    ->badge()
                    ->color('gray')
                    ->placeholder('manual'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'tentative' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('start_at', 'desc')
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Type')
                    ->options([
                        'meeting' => 'Meeting',
                        'rehearsal' => 'Rehearsal',
                        'personal' => 'Personal',
                        'focus' => 'Focus',
                        'other' => 'Other',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'confirmed' => 'Confirmed',
                        'tentative' => 'Tentative',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('source')
                    ->options([
                        'google' => 'Google',
                        'manual' => 'Manual',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
