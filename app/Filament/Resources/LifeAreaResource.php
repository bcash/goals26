<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LifeAreaResource\Pages;
use App\Models\LifeArea;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LifeAreaResource extends Resource
{
    protected static ?string $model = LifeArea::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Life Areas';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Life Area')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(50)
                        ->placeholder('e.g. Creative'),

                    TextInput::make('icon')
                        ->label('Icon (emoji or Heroicon)')
                        ->placeholder('heroicon-o-star')
                        ->required(),
                ]),

                Grid::make(2)->schema([
                    ColorPicker::make('color_hex')
                        ->label('Colour')
                        ->required(),

                    TextInput::make('sort_order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(0),
                ]),

                Textarea::make('description')
                    ->rows(2)
                    ->placeholder('What does this area of your life cover?')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color_hex')
                    ->label(''),

                TextColumn::make('icon')
                    ->label('')
                    ->size('lg'),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->limit(60)
                    ->color('gray'),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLifeAreas::route('/'),
            'create' => Pages\CreateLifeArea::route('/create'),
            'edit' => Pages\EditLifeArea::route('/{record}/edit'),
        ];
    }
}
