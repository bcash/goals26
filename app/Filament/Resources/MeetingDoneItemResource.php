<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingDoneItemResource\Pages;
use App\Models\MeetingDoneItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MeetingDoneItemResource extends Resource
{
    protected static ?string $model = MeetingDoneItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Goals & Projects';

    protected static ?string $navigationLabel = 'Done & Delivered';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Done Item')->schema([
                Select::make('meeting_id')
                    ->label('Client Meeting')
                    ->relationship('meeting', 'title')
                    ->required()
                    ->searchable(),

                TextInput::make('title')
                    ->label('What was delivered')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),

            Section::make('Impact & Testimonial')->schema([
                Grid::make(2)->schema([
                    TextInput::make('outcome_metric')
                        ->label('Quantified Result')
                        ->placeholder('e.g. 25% efficiency gain, 3 new clients')
                        ->nullable(),

                    TextInput::make('value_delivered')
                        ->label('Value Delivered ($)')
                        ->numeric()
                        ->prefix('$')
                        ->nullable(),
                ]),

                Textarea::make('client_quote')
                    ->label('Client Quote')
                    ->rows(2)
                    ->placeholder('Their words about the delivery')
                    ->columnSpanFull(),

                Toggle::make('save_as_testimonial')
                    ->label('Save as testimonial for reuse')
                    ->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meeting.meeting_date')
                    ->label('When')
                    ->date('M j, Y')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('What was delivered')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('outcome_metric')
                    ->label('Quantified Result')
                    ->color('success'),

                TextColumn::make('client_quote')
                    ->label('Their words')
                    ->limit(50)
                    ->color('gray'),

                IconColumn::make('save_as_testimonial')
                    ->label('Testimonial')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetingDoneItems::route('/'),
            'create' => Pages\CreateMeetingDoneItem::route('/create'),
            'edit' => Pages\EditMeetingDoneItem::route('/{record}/edit'),
        ];
    }
}
