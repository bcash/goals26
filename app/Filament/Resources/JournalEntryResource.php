<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalEntryResource\Pages;
use App\Models\JournalEntry;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Journal';

    protected static ?string $navigationLabel = 'Journal';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Grid::make(3)->schema([
                    DatePicker::make('entry_date')
                        ->required()
                        ->default(today()),

                    Select::make('entry_type')
                        ->options([
                            'morning' => 'Morning',
                            'evening' => 'Evening',
                            'weekly' => 'Weekly',
                            'freeform' => 'Freeform',
                        ])
                        ->default('freeform')
                        ->required(),

                    Select::make('mood')
                        ->label('Mood')
                        ->options([
                            1 => '1 - Rough',
                            2 => '2 - Low',
                            3 => '3 - Okay',
                            4 => '4 - Good',
                            5 => '5 - Great',
                        ])
                        ->nullable(),
                ]),

                MarkdownEditor::make('content')
                    ->required()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold', 'italic', 'bulletList', 'orderedList',
                        'heading', 'blockquote', 'link',
                    ]),

                TagsInput::make('tags')
                    ->separator(',')
                    ->nullable()
                    ->columnSpanFull(),

                Placeholder::make('ai_insights')
                    ->label('AI Insights')
                    ->content(fn ($record) => $record?->ai_insights ?? 'No AI insights yet.')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entry_date')
                    ->label('Date')
                    ->date('l, M j, Y')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('entry_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'morning' => 'warning',
                        'evening' => 'info',
                        'weekly' => 'success',
                        'freeform' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('mood')
                    ->label('Mood')
                    ->formatStateUsing(fn ($state) => match ((int) $state) {
                        1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5',
                        default => '-',
                    })
                    ->alignCenter(),

                TextColumn::make('content')
                    ->limit(80)
                    ->color('gray'),
            ])
            ->defaultSort('entry_date', 'desc')
            ->filters([
                SelectFilter::make('entry_type')
                    ->options([
                        'morning' => 'Morning', 'evening' => 'Evening',
                        'weekly' => 'Weekly', 'freeform' => 'Freeform',
                    ]),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
