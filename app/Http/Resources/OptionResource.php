<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @mixin TModel
 */
class OptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id:int|string,name:string}
     */
    public function toArray(Request $request): array
    {
        $model = $this->resource;

        if (! $model instanceof Model) {
            throw new \InvalidArgumentException('OptionResource expects an Eloquent model resource.');
        }

        $identifier = $model->getAttribute($model->getKeyName());

        if (! is_int($identifier) && ! is_string($identifier)) {
            throw new \UnexpectedValueException('OptionResource requires the model identifier to be an integer or string.');
        }

        $name = $model->getAttribute('name');
        if (! is_string($name)) {
            throw new \UnexpectedValueException('OptionResource requires the model to provide a name string attribute.');
        }

        return [
            'id' => $identifier,
            'name' => $name,
        ];
    }
}
