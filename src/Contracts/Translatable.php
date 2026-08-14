<?php

namespace VivereStage\LaravelApiFeeds\Contracts;

interface Translatable
{
    public function getTranslations(string $attribute);
    public function getTranslation(string $attribute, string $locale);
    public function translatableAttributes(): array;
}
