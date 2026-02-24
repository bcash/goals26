<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\ModelResolver;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class InspectModel extends Tool
{
    public function __construct(
        protected string $modelSlug,
        protected array $modelConfig,
    ) {}

    public function name(): string
    {
        return "inspect-{$this->modelSlug}";
    }

    public function title(): string
    {
        return "Inspect {$this->modelConfig['label']}";
    }

    public function description(): string
    {
        return "Returns the schema, fillable fields, casts, relationships, and available filters for the {$this->modelConfig['label']} model.";
    }

    /**
     * No input parameters needed — this is a zero-arg introspection tool.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response
    {
        $schema = ModelResolver::schema($this->modelSlug);

        return Response::json($schema);
    }
}
