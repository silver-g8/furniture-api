<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * @phpstan-type ProductMeta array{
 *     index_fields: list<string>,
 *     form_fields: list<array{
 *         key: string,
 *         label: string,
 *         component: string,
 *         rules: list<string>,
 *         props?: array<string, mixed>|\stdClass
 *     }>,
 *     show_fields: list<string>
 * }
 */
class ProductMetaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return ProductMeta
     */
    public function toArray(Request $request): array
    {
        /** @var ProductMeta $meta */
        $meta = $this->resource;

        return [
            'index_fields' => $meta['index_fields'],
            'form_fields' => array_map(
                static function (array $field): array {
                    $normalized = Arr::only($field, ['key', 'label', 'component', 'rules', 'props']);

                    return [
                        'key' => $normalized['key'] ?? '',
                        'label' => $normalized['label'] ?? '',
                        'component' => $normalized['component'] ?? '',
                        'rules' => array_values($normalized['rules'] ?? []),
                        'props' => $normalized['props'] ?? new \stdClass,
                    ];
                },
                $meta['form_fields'],
            ),
            'show_fields' => $meta['show_fields'],
        ];
    }
}
