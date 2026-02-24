<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingDoneItemResource\Pages;
use App\Models\MeetingDoneItem;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, Grid, TextInput, Textarea, Select, Toggle};
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Actions\{EditAction, DeleteAction};
use Filament\Tables\Actions\{BulkActionGroup, DeleteBulkAction};

class MeetingDoneItemResource extends Resource
{
    protected static ?string $model = MeetingDoneItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationGroup = 'Goals & Projects';
    protected static ?string $navigationLabel = 'Done & Delivered';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
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
            'index'  => Pages\ListMeetingDoneItems::route('/'),
            'create' => Pages\CreateMeetingDoneItem::route('/create'),
            'edit'   => Pages\EditMeetingDoneItem::route('/{record}/edit'),
        ];
    }
}
