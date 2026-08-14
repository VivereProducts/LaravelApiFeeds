<?php

namespace VivereStage\LaravelApiFeeds\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasTranslations
{
    protected bool $keepAllTranslations = false;

    public function getTranslations(string $attribute)
    {
        if (!in_array($attribute, $this->translatableAttributes())) {
            return [];
        }

        return parent::getAttributeValue($attribute);
    }

    public function getTranslation(string $attribute, string $locale, $fallback = null)
    {
        if (in_array($attribute, $this->translatableAttributes())) {
            return data_get(parent::getAttributeValue($attribute), $locale, $fallback);
        }

        return parent::getAttributeValue($attribute) ?? $fallback;
    }

    public function getAttributeValue($key)
    {
        if (in_array($key, $this->translatableAttributes())) {
            return $this->getTranslationWithFallback($key);
        }
        return parent::getAttributeValue($key);
    }

    public function getTranslationWithFallback($key, $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $translations = parent::getAttributeValue($key);

        // Some legacy records contain a scalar JSON value instead of a locale map.
        if (!is_array($translations)) {
            return $translations;
        }

        $value = data_get($translations, $locale);
        if (empty($value)) {
            $value = data_get($translations, config('general.fallback_locale', 'en'));
        }
        if (empty($value)) {
            $value = Arr::first(array_filter($translations));
        }

        return $value;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (in_array($field, $this->translatableAttributes())) {
            return self::query()
                ->whereRaw(
                    "JSON_SEARCH(`{$this->getTable()}`.`$field`, 'one', ?) IS NOT NULL",
                    [ $value ]
                )
                ->firstOrFail();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function toArray()
    {
        $array = Arr::map(parent::toArray(), function ($item, $key) {
            if (in_array($key, $this->translatableAttributes()) && $this->keepAllTranslations == false) {
                return $this->getTranslationWithFallback($key);
            }

            return $item;
        });

        if ($this->keepAllTranslations == true) {
            foreach ($this->translatableAttributes() as $transAttribute) {
            if (empty($this->getAttributeValue($transAttribute))) {
                    $array[$transAttribute] = app('languages')->mapWithKeys(function ($language) {
                        return [
                            $language->locale => ''
                        ];
                    });
                }

                $array['current_locale_data'][$transAttribute] = $this->getAttribute($transAttribute);
            }
        }

        return $array;
    }

    public function keepAllTranslations(bool $keepAllTranslations = true): static
    {
        $this->keepAllTranslations = $keepAllTranslations;

        return $this;
    }

    public function newCollection(array $models = [])
    {
        $collection = parent::newCollection($models);
        $this->addTranslatableMacrosToCollection($collection);
        return $collection;
    }

    protected function addTranslatableMacrosToCollection(Collection $collection)
    {
        $collection->macro('keepAllTranslations', function (bool $keepAllTranslations = true) {
            $this->each(function ($model) use ($keepAllTranslations) {
                $model->keepAllTranslations($keepAllTranslations);
            });
        });
    }
}
