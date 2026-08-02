<?php

namespace VivereStage\LaravelApiFeeds\Models;

use Illuminate\Support\Arr;
use VivereStage\LaravelApiFeeds\Eloquent\Model;

class Media extends Model
{
    public function getUrl(?string $conversion = null, ?string $type = null)
    {
        if (empty($conversion)) {
            return $this->url;
        }

        $conversion = $this->generated_conversions[$conversion] ?? null;
        if (empty($conversion)) {
            return null;
        }

        if (!empty($type)) {
            $file = $conversion[$type];
        } else {
            $file = Arr::first($conversion);
        }

        if (empty($file)) {
            return null;
        }

        return $file['url'] ?? null;
    }
}
