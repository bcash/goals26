---
name: laravel-filament-development
description: "Filament v3 resource patterns, form builders, table builders, widgets, pages, and relation managers. Activates when building or modifying Filament resources, forms, tables, widgets, admin panel pages, or Livewire components; or when the user mentions Filament, resource, widget, dashboard, form, table, admin, or panel."
---

# Laravel Filament v3 Development

## When to Apply

Activate this skill when:

- Creating or modifying Filament resources (CRUD)
- Building form schemas or table column definitions
- Creating or editing widgets (stats, charts, custom)
- Working on custom Filament pages
- Implementing relation managers
- Customizing the admin panel layout or navigation

## Search First

Before implementing, ALWAYS:
1. Check sibling resources in `app/Filament/Resources/` for existing patterns
2. Check sibling widgets in `app/Filament/Widgets/` for conventions
3. Use `search-docs` with queries like `['filament resource', 'filament form']`
4. Review the model's fillable, casts, and relationships

## Project Structure

```
app/Filament/
├── Pages/
│   ├── Dashboard.php          # Custom dashboard with 10 widgets
│   └── TaskTree.php           # Interactive task tree page (Livewire)
├── Resources/                 # 19 Filament resources
│   ├── TaskResource.php
│   ├── TaskResource/
│   │   └── Pages/
│   │       ├── CreateTask.php
│   │       ├── EditTask.php
│   │       └── ListTasks.php
│   └── ...
└── Widgets/                   # 10 dashboard widgets
    ├── BaseWidget.php         # Abstract base — ALL widgets extend this
    ├── GoalProgressWidget.php
    └── ...
```

## Resource Patterns

### Table Definitions

Follow this pattern for all resource tables:

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'active' => 'success',
                    'paused' => 'warning',
                    'completed' => 'info',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('lifeArea.name')
                ->label('Life Area')
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'active' => 'Active',
                    'paused' => 'Paused',
                    'completed' => 'Completed',
                ]),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}
```

### Form Definitions

```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Details')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('life_area_id')
                        ->relationship('lifeArea', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'paused' => 'Paused',
                            'completed' => 'Completed',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
}
```

### Key Conventions

- **Status badges**: Always use `->badge()->color(fn ...)` with match expressions
- **Relationships**: Use `->relationship('name', 'displayColumn')->searchable()->preload()`
- **Dates**: Use `->dateTime()` for timestamps, `->date()` for date-only fields
- **Money fields**: Display as-is (dollars, not cents) — use `->money('USD')` or `->numeric(decimalPlaces: 2)`
- **Toggle hidden**: Put `created_at` and `updated_at` in `->toggleable(isToggledHiddenByDefault: true)`
- **Sections**: Group related fields in `Section::make()` with `->columns(2)` for side-by-side layout

## Widget Patterns

### ALL widgets extend BaseWidget

```php
namespace App\Filament\Widgets;

class MyWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 2;
    protected static string $view = 'filament.widgets.my-widget';

    public function getViewData(): array
    {
        // Query data here
        return ['items' => $items];
    }
}
```

### Widget views location

All widget Blade views are in `resources/views/filament/widgets/`.

## Custom Pages (Livewire)

Custom pages extend `Filament\Pages\Page` and use Livewire properties:

```php
class TaskTree extends Page
{
    protected static string $view = 'filament.pages.task-tree';

    public ?int $projectId = null;
    public array $tree = [];

    public function mount(): void { $this->loadTree(); }

    // Use wire:model.live for reactive filters
    public function updatedProjectId(): void { $this->loadTree(); }
}
```

## Navigation

- Group resources under `protected static ?string $navigationGroup = 'Goals & Projects';`
- Set sort with `protected static ?int $navigationSort = 5;`
- Use Heroicon names: `protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';`

## Common Pitfalls

- Don't use `DB::` queries in resources — use Eloquent relationships
- Don't forget `->searchable()` and `->sortable()` on frequently used columns
- Always include a `SelectFilter` for status/enum columns
- Remember `->preload()` on Select components that reference small tables
- Use `->columnSpanFull()` for textarea and rich text fields
- For PostgreSQL: use `ilike` in custom filters, not `like`
