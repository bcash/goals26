<?php

namespace App\Mcp\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class ModelResolver
{
    /**
     * Create a query builder for the given model slug.
     * Bypasses tenant scopes since MCP runs local-only without auth.
     */
    public static function query(string $slug): Builder
    {
        $config = ModelRegistry::get($slug);

        if (! $config) {
            throw new \InvalidArgumentException("Unknown model slug: {$slug}");
        }

        /** @var Model $model */
        $model = new $config['class'];

        return $model->newQuery()->withoutGlobalScopes();
    }

    /**
     * Find a single record by ID, bypassing tenant scopes.
     * Eager-loads BelongsTo relationships for context.
     */
    public static function find(string $slug, int|string $id): ?Model
    {
        $config = ModelRegistry::get($slug);

        if (! $config) {
            throw new \InvalidArgumentException("Unknown model slug: {$slug}");
        }

        return static::query($slug)
            ->with($config['belongsTo'])
            ->find($id);
    }

    /**
     * Get schema introspection for a model: table, fillable, casts,
     * hidden, and all BelongsTo relationships.
     *
     * @return array<string, mixed>
     */
    public static function schema(string $slug): array
    {
        $config = ModelRegistry::get($slug);

        if (! $config) {
            throw new \InvalidArgumentException("Unknown model slug: {$slug}");
        }

        /** @var Model $model */
        $model = new $config['class'];

        return [
            'slug' => $slug,
            'label' => $config['label'],
            'table' => $model->getTable(),
            'fillable' => $model->getFillable(),
            'casts' => $model->getCasts(),
            'hidden' => $model->getHidden(),
            'relationships' => static::discoverRelationships($model),
            'filters' => $config['filters'],
            'searchable' => $config['searchable'],
            'dates' => $config['dates'],
            'has_tenant' => $config['hasTenant'],
        ];
    }

    /**
     * Discover all BelongsTo, HasMany, HasOne relationships via reflection.
     *
     * @return array<string, array{type: string, related: string}>
     */
    public static function discoverRelationships(Model $model): array
    {
        $relationships = [];
        $reflection = new ReflectionClass($model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip non-model methods, inherited base methods, and methods with parameters
            if ($method->class !== get_class($model)) {
                continue;
            }

            if ($method->getNumberOfParameters() > 0) {
                continue;
            }

            $returnType = $method->getReturnType();

            if (! $returnType) {
                continue;
            }

            $typeName = $returnType instanceof \ReflectionNamedType ? $returnType->getName() : '';

            $relationTypes = [
                'Illuminate\Database\Eloquent\Relations\BelongsTo' => 'belongsTo',
                'Illuminate\Database\Eloquent\Relations\HasMany' => 'hasMany',
                'Illuminate\Database\Eloquent\Relations\HasOne' => 'hasOne',
                'Illuminate\Database\Eloquent\Relations\BelongsToMany' => 'belongsToMany',
            ];

            if (! isset($relationTypes[$typeName])) {
                continue;
            }

            try {
                $relation = $model->{$method->getName()}();
                $relationships[$method->getName()] = [
                    'type' => $relationTypes[$typeName],
                    'related' => get_class($relation->getRelated()),
                ];
            } catch (\Throwable) {
                // Skip relationships that can't be instantiated
            }
        }

        return $relationships;
    }
}
