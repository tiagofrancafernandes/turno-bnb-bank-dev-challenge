<?php

namespace App\Enums\Traits;

trait HasLabel
{
    public function label(bool $translate = false): string
    {
        return $translate ? $this->localeLabel() : $this->titleLabel();
    }

    public function titleLabel()
    {
        return str($this->name)->title()->toString();
    }

    public function localeLabel(): string
    {
        return __(
            str(class_basename(static::class))
                ->kebab()
                ->prepend('enums/')
                ->append('.')
                ->append($this->name)
                ->toString()
        );
    }
}
