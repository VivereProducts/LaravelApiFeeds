<?php

namespace VivereStage\LaravelApiFeeds\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Date implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        if (empty($value['value'])) {
            return null;
        }
        $date = Carbon::parse($value['value']);
        if (!empty($value['timezone'])) {
            $date->shiftTimezone($value['timezone']);
        }

        return $date;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if (! ($value instanceof Carbon)) {
            return null;
        }

        return [
            'value' => $value->format('Y-m-d H:i:s'),
            'timezone' => $value->getTimezone()->getName(),
        ];
    }
}
