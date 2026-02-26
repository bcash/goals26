<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailConversationResource\Pages;
use App\Models\EmailConversation;
use App\Models\FreeScoutMailbox;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmailConversationResource extends Resource
{
    protected static ?string $model = EmailConversation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Communications';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('freescout_conversation_id')
                ->label('FreeScout Conversation ID')
                ->numeric()
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('subject')
                ->required()
                ->maxLength(500)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('email_contact_id')
                    ->label('Contact')
                    ->relationship('contact', 'email')
                    ->searchable()
                    ->nullable(),

                Select::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->nullable(),
            ]),

            Grid::make(2)->schema([
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Pending',
                        'closed' => 'Closed',
                        'spam' => 'Spam',
                    ])
                    ->default('active'),

                Select::make('type')
                    ->options([
                        'email' => 'Email',
                        'phone' => 'Phone',
                        'chat' => 'Chat',
                    ])
                    ->default('email'),
            ]),

            Grid::make(2)->schema([
                Select::make('importance')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ])
                    ->default('normal'),

                Select::make('category')
                    ->options([
                        'support' => 'Support',
                        'sales' => 'Sales',
                        'vendor' => 'Vendor',
                        'supplier' => 'Supplier',
                        'billing' => 'Billing',
                        'general' => 'General',
                    ])
                    ->default('general'),
            ]),

            Grid::make(2)->schema([
                TextInput::make('assigned_to_name')
                    ->label('Assigned To (Name)')
                    ->maxLength(255),

                TextInput::make('assigned_to_email')
                    ->label('Assigned To (Email)')
                    ->email()
                    ->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contact.first_name')
                    ->label('Customer')
                    ->formatStateUsing(fn ($record) => $record->contact?->fullName() ?? 'Unknown')
                    ->description(fn ($record) => $record->contact?->email)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $like = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

                        return $query->where(function (Builder $q) use ($search, $like) {
                            $q->whereHas('contact', function (Builder $contactQuery) use ($search, $like) {
                                $contactQuery->where('first_name', $like, "%{$search}%")
                                    ->orWhere('last_name', $like, "%{$search}%")
                                    ->orWhere('email', $like, "%{$search}%");
                            });
                        });
                    }),

                TextColumn::make('subject')
                    ->label('Conversation')
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->preview ? \Illuminate\Support\Str::limit($record->preview, 80) : null)
                    ->searchable()
                    ->wrap()
                    ->grow(),

                TextColumn::make('freescout_conversation_id')
                    ->label('Number')
                    ->formatStateUsing(fn ($state) => "#{$state}")
                    ->sortable(),

                TextColumn::make('last_message_at')
                    ->label('Waiting Since')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'closed' => 'gray',
                        'spam' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                SelectFilter::make('freescout_mailbox_id')
                    ->label('Mailbox')
                    ->options(fn () => FreeScoutMailbox::query()
                        ->where('user_id', auth()->id())
                        ->pluck('name', 'freescout_mailbox_id')),

                TernaryFilter::make('needs_review'),
            ])
            ->actions([ViewAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailConversations::route('/'),
            'create' => Pages\CreateEmailConversation::route('/create'),
            'view' => Pages\ViewEmailConversation::route('/{record}'),
            'edit' => Pages\EditEmailConversation::route('/{record}/edit'),
        ];
    }
}
