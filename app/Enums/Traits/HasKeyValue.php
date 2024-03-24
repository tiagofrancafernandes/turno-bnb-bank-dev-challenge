<?php

namespace App\Enums\Traits;

use Illuminate\Support\Collection;

trait HasKeyValue
{
    use HasLabel;

    public static function keyValue(
        int|string $key = 'name',
        int|string $value = 'value',
    ): Collection {
        return static::all()
            ->pluck($value, $key);
    }

    public static function all(): Collection
    {
        return collect(static::cases())
            ->map(fn ($item) => [
                'name' => $item->name,
                'value' => $item->value,
                'label' => $item->label() ?? null,
                'localeLabel' => $item->localeLabel() ?? null,
            ]);
    }
}
