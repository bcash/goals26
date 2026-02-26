<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailContactResource\Pages;
use App\Models\EmailContact;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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

class EmailContactResource extends Resource
{
    protected static ?string $model = EmailContact::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(2)->schema([
                TextInput::make('first_name')
                    ->maxLength(255),

                TextInput::make('last_name')
                    ->maxLength(255),
            ]),

            Grid::make(2)->schema([
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->tel()
                    ->maxLength(50),
            ]),

            Grid::make(2)->schema([
                TextInput::make('company')
                    ->maxLength(255),

                TextInput::make('job_title')
                    ->maxLength(255),
            ]),

            Grid::make(2)->schema([
                Select::make('contact_type')
                    ->options([
                        'client' => 'Client',
                        'vendor' => 'Vendor',
                        'supplier' => 'Supplier',
                        'partner' => 'Partner',
                        'team' => 'Team',
                        'personal' => 'Personal',
                        'other' => 'Other',
                    ])
                    ->default('other'),

                Select::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->nullable(),
            ]),

            Section::make('VPO Integration')
                ->description('Link to a VPO account and contact for CRM integration')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('vpo_account_id')
                            ->label('VPO Account ID')
                            ->placeholder('VPO account identifier')
                            ->nullable()
                            ->maxLength(50),

                        TextInput::make('vpo_contact_id')
                            ->label('VPO Contact ID')
                            ->placeholder('VPO contact identifier')
                            ->nullable()
                            ->maxLength(50),
                    ]),
                ]),

            Textarea::make('notes')
                ->rows(3)
                ->columnSpanFull(),

            TagsInput::make('tags')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label('Name')
                    ->formatStateUsing(fn ($record) => $record->fullName())
                    ->searchable(['first_name', 'last_name']),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('company')
                    ->searchable(),

                TextColumn::make('contact_type')
                    ->badge(),

                TextColumn::make('conversation_count')
                    ->label('Convos'),

                TextColumn::make('last_contact_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('last_contact_at', 'desc')
            ->filters([
                SelectFilter::make('contact_type')
                    ->options([
                        'client' => 'Client',
                        'vendor' => 'Vendor',
                        'supplier' => 'Supplier',
                        'partner' => 'Partner',
                        'team' => 'Team',
                        'personal' => 'Personal',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailContacts::route('/'),
            'create' => Pages\CreateEmailContact::route('/create'),
            'view' => Pages\ViewEmailContact::route('/{record}'),
            'edit' => Pages\EditEmailContact::route('/{record}/edit'),
        ];
    }
}
