<?php

namespace VivereStage\LaravelApiFeeds\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\Response;
use LogicException;
use Nette\NotImplementedException;

abstract class Model extends EloquentModel
{
    /**
     * Custom endpoint
     *
     * @var string
     */
    protected string $endpoint;

    /**
     * Define the relationships
     *
     * @var array
     */
    protected array $related = [];

    /**
     * Reverse relations
     *
     * @var array
     */
    public array $reverseRelations;

    public function __construct(array $attributes = [])
    {
        foreach ($this->related as $relationName => $relationType) {
            /**
             * @var self $relationInstance
             */
            $relationInstance = new $relationType;
            $relationData = $attributes[$relationName] ?? null;

            if (!empty($relationData[$relationInstance->getKeyName()])) {
                // Single relation
                $this->relations[$relationName] = new $relationType($relationData);
            } elseif (is_array($relationData)) {
                // Multiple relation
                $this->relations[$relationName] = collect($relationData)->mapInto($relationType);
            } elseif (is_null($relationData) && method_exists($relationInstance, 'getDefaultAttributes')) {
                // Default data
                $this->relations[$relationName] = new $relationType($relationInstance->getDefaultAttributes());
            }

            unset($attributes[$relationName]);
        }

        $this->reverseRelations = $attributes['reverse_relations'] ?? [];
        unset($attributes['reverse_relations']);

        $this->attributes = $attributes;
    }

    public function newQuery(): Builder
    {
        return new Builder($this);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->newQuery()
            ->where($field ?? $this->getKey(), $value)
            ->firstOrFail();
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        throw new NotImplementedException(
            'Not implemented.', Response::HTTP_NOT_IMPLEMENTED
        );
    }

    public function getEndpoint(): string
    {
        if (!empty($this->endpoint)) {
            return $this->endpoint;
        }

        return (string) str(class_basename($this))->kebab();
    }

    public function save(array $options = [])
    {
        throw new LogicException('API models are read-only.');
    }

    public function delete()
    {
        throw new LogicException('API models are read-only.');
    }
}
