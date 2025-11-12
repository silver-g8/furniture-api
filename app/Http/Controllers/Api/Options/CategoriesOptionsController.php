<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Options;

use App\Http\Controllers\Controller;
use App\Http\Resources\OptionResource;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CategoriesOptionsController extends Controller
{
    use AuthorizesRequests;

    #[OA\Get(
        path: '/api/v1/categories/options',
        summary: 'List category options',
        description: 'Return lightweight category options for dropdowns',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of categories formatted for select components',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Option')
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $payload = OptionResource::collection($categories)
            ->resolve(request());

        return response()->json($payload);
    }
}
