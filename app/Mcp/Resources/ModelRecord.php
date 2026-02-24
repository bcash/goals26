<?php

namespace App\Mcp\Resources;

use App\Mcp\Support\ModelResolver;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

class ModelRecord extends Resource implements HasUriTemplate
{
    protected string $mimeType = 'application/json';

    public function __construct(
        protected string $modelSlug,
        protected array $modelConfig,
    ) {}

    public function name(): string
    {
        return "{$this->modelSlug}-record";
    }

    public function title(): string
    {
        return "{$this->modelConfig['label']} Record";
    }

    public function description(): string
    {
        return "Fetch a single {$this->modelConfig['label']} record by ID with its BelongsTo relationships loaded.";
    }

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate("{$this->modelSlug}://solas-run/{id}");
    }

    public function handle(Request $request): Response
    {
        $id = $request->get('id');

        if (! $id) {
            return Response::error("Missing required parameter: id");
        }

        $record = ModelResolver::find($this->modelSlug, $id);

        if (! $record) {
            return Response::error("{$this->modelConfig['label']} #{$id} not found.");
        }

        return Response::json($record->toArray());
    }
}
