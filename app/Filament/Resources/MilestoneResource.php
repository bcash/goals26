<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MilestoneResource\Pages;
use App\Models\Milestone;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MilestoneResource extends Resource
{
    protected static ?string $model = Milestone::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static string|\UnitEnum|null $navigationGroup = 'Goals & Projects';

    protected static ?string $navigationLabel = 'Milestones';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Select::make('goal_id')
                    ->label('Goal')
                    ->relationship('goal', 'title')
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    DatePicker::make('due_date')->nullable(),
                    Select::make('status')
                        ->options(['pending' => 'Pending', 'complete' => 'Complete'])
                        ->default('pending'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('goal.title')->label('Goal')->searchable()->limit(40),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('due_date')->date('M j, Y')->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state) => $state === 'complete' ? 'success' : 'warning'),
            ])
            ->defaultSort('due_date')
            ->filters([
                SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'complete' => 'Complete']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMilestones::route('/'),
            'create' => Pages\CreateMilestone::route('/create'),
            'edit' => Pages\EditMilestone::route('/{record}/edit'),
        ];
    }
}
