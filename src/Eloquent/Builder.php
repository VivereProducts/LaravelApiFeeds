<?php

namespace VivereStage\LaravelApiFeeds\Eloquent;

use Carbon\Carbon;
use DateTime;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use VivereStage\LaravelApiFeeds\Connection\ApiConnection;

class Builder
{
    protected ApiConnection $connection;

    protected Model $model;

    protected array $query = [];

    public function __construct(Model $model)
    {
        $this->connection = new ApiConnection;
        $this->model = $model;
    }

    public function where(...$parameters): self
    {
        if (is_array($parameters[0] ?? null)) {
            $this->whereArray($parameters[0]);
        }
        if (count($parameters) == 2) {
            [$column, $value] = $parameters;
            $operator = '=';
        } elseif (count($parameters) == 3) {
            [$column, $operator, $value] = $parameters;
        }

        $this->singleWhere($column, $operator, $value);

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->query['filter'][$column]['operator'] = 'in';
        $this->query['filter'][$column]['value'] = $values;

        return $this;
    }

    protected function singleWhere(
        string $column,
        string $operator,
        mixed $value
    ): self {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        if ($value instanceof DateTimeInterface) {
            $timezone = $value->getTimezone()->getName();
            $value = $value->format('Y-m-d H:i:s');
        }

        $this->query['filter'][$column]['operator'] = $operator;
        $this->query['filter'][$column]['value'] = $value;
        if (!empty($timezone)) {
            $this->query['filter'][$column]['timezone'] = $timezone;
        }

        return $this;
    }

    protected function whereArray(array $whereArray): self
    {
        foreach ($whereArray as $thisWhere) {
            $this->where(...$thisWhere);
        }

        return $this;
    }

    public function sortBy(string $column, string $direction = 'asc'): self
    {
        if (!isset($this->query['sortBy'])) {
            $this->query['sortBy'] = [];
        }

        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';

        $this->query['sortBy'][] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function unsetSortBy(): self
    {
        unset($this->query['sortBy']);

        return $this;
    }

    public function withNeighbours(bool $wrap = false): self
    {
        $this->query['withNeighbours'] = 1;
        if ($wrap == true) {
            $this->query['wrapNeighbours'] = 1;
        }

        return $this;
    }

    public function withoutNeighbours(): self
    {
        unset(
            $this->query['withNeighbours'],
            $this->query['wrapNeighbours'],
        );

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query['limit'] = $limit;

        return $this;
    }

    public function page(int $page): self
    {
        $this->query['page'] = $page;

        return $this;
    }

    public function get(): LengthAwarePaginator
    {
        $data = $this->connection->get($this->model->getEndpoint(), $this->query);
        $items = Collection::wrap($data['data'])->mapInto(get_class($this->model));
        return new LengthAwarePaginator(
            $items,
            $data['total'],
            $data['per_page'],
            $this->query['page'] ?? 1
        );
    }

    public function first()
    {
        return $this->limit(1)->get()->first();
    }

    public function firstOrFail()
    {
        $entry = $this->first();
        if (empty($first)) {
            throw new ModelNotFoundException(
                'No results for ' . get_class($this) . '.'
            );
        }

        return $entry;
    }

    public function getModel(): Model
    {
        return $this->model;
    }
}
