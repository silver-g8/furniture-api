<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Options;

use App\Http\Controllers\Controller;
use App\Http\Resources\OptionResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class BrandsOptionsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/brands/options',
        summary: 'List brand options',
        description: 'Return lightweight brand options for dropdowns',
        security: [['bearerAuth' => []]],
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of brands formatted for select components',
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
        $brands = Brand::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $payload = OptionResource::collection($brands)
            ->resolve(request());

        return response()->json($payload);
    }
}
