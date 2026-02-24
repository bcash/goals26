<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\ModelRegistry;
use App\Mcp\Support\ModelResolver;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListModels extends Tool
{
    public function __construct(
        protected string $modelSlug,
        protected array $modelConfig,
    ) {}

    public function name(): string
    {
        return "list-{$this->modelSlug}";
    }

    public function title(): string
    {
        return "List {$this->modelConfig['label']}s";
    }

    public function description(): string
    {
        $desc = "Query {$this->modelConfig['label']} records with optional filters, date ranges, full-text search, and pagination.";

        if (! empty($this->modelConfig['filters'])) {
            $filterNames = implode(', ', array_keys($this->modelConfig['filters']));
            $desc .= " Filterable by: {$filterNames}.";
        }

        if (! empty($this->modelConfig['searchable'])) {
            $searchFields = implode(', ', $this->modelConfig['searchable']);
            $desc .= " Searchable fields: {$searchFields}.";
        }

        return $desc;
    }

    public function schema(JsonSchema $schema): array
    {
        $properties = [];

        // Dynamic enum filters from the registry
        foreach ($this->modelConfig['filters'] as $filterName => $allowedValues) {
            $properties[$filterName] = $schema->string()
                ->description("Filter by {$filterName}")
                ->enum($allowedValues)
                ->nullable();
        }

        // Search across text fields
        if (! empty($this->modelConfig['searchable'])) {
            $fields = implode(', ', $this->modelConfig['searchable']);
            $properties['search'] = $schema->string()
                ->description("Case-insensitive search across: {$fields}")
                ->nullable();
        }

        // Date range filters
        foreach ($this->modelConfig['dates'] as $dateField) {
            $properties["{$dateField}_from"] = $schema->string()
                ->description("Filter {$dateField} >= this date (YYYY-MM-DD)")
                ->format('date')
                ->nullable();

            $properties["{$dateField}_to"] = $schema->string()
                ->description("Filter {$dateField} <= this date (YYYY-MM-DD)")
                ->format('date')
                ->nullable();
        }

        // Pagination
        $properties['page'] = $schema->integer()
            ->description('Page number (default: 1)')
            ->nullable();

        $properties['per_page'] = $schema->integer()
            ->description('Results per page (default: 25, max: 100)')
            ->nullable();

        // Ordering
        $properties['order_by'] = $schema->string()
            ->description('Column to order by (default: created_at)')
            ->nullable();

        $properties['order_dir'] = $schema->string()
            ->description('Sort direction')
            ->enum(['asc', 'desc'])
            ->nullable();

        return $properties;
    }

    public function handle(Request $request): Response
    {
        $query = ModelResolver::query($this->modelSlug);

        // Apply enum filters
        foreach ($this->modelConfig['filters'] as $filterName => $allowedValues) {
            $value = $request->get($filterName);

            if ($value !== null && in_array($value, $allowedValues, true)) {
                $query->where($filterName, $value);
            }
        }

        // Apply full-text search (PostgreSQL ilike)
        $search = $request->get('search');

        if ($search && ! empty($this->modelConfig['searchable'])) {
            $query->where(function ($q) use ($search) {
                foreach ($this->modelConfig['searchable'] as $field) {
                    $q->orWhere($field, 'ilike', "%{$search}%");
                }
            });
        }

        // Apply date range filters
        foreach ($this->modelConfig['dates'] as $dateField) {
            $from = $request->get("{$dateField}_from");
            $to = $request->get("{$dateField}_to");

            if ($from) {
                $query->where($dateField, '>=', $from);
            }

            if ($to) {
                $query->where($dateField, '<=', $to);
            }
        }

        // Ordering
        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');

        // Safety: only allow ordering by known columns
        $model = $query->getModel();
        $allowedColumns = array_merge(
            $model->getFillable(),
            ['id', 'created_at', 'updated_at'],
        );

        if (in_array($orderBy, $allowedColumns, true)) {
            $query->orderBy($orderBy, $orderDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Eager-load BelongsTo relationships
        if (! empty($this->modelConfig['belongsTo'])) {
            $query->with($this->modelConfig['belongsTo']);
        }

        // Paginate
        $page = max(1, (int) ($request->get('page', 1)));
        $perPage = min(100, max(1, (int) ($request->get('per_page', 25))));

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return Response::json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
