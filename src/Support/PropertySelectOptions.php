<?php

namespace Afterburner\Communications\Support;

use Illuminate\Support\Collection;

class PropertySelectOptions
{
    /**
     * @param  Collection<int, object{id: int, lot_number: mixed}>  $properties
     * @param  array<int, int|string>  $selectedIds
     * @return array<int, array{value: string, label: string}>
     */
    public static function forSelect(Collection $properties, array $selectedIds = []): array
    {
        $options = $properties
            ->map(fn ($property) => [
                'value' => (string) $property->id,
                'label' => __('Lot').' '.$property->lot_number,
            ])
            ->all();

        $selected = collect($selectedIds)
            ->map(fn ($id) => (string) $id)
            ->all();

        foreach ($selected as $selectedId) {
            if (! self::containsValue($options, $selectedId)) {
                $options[] = [
                    'value' => $selectedId,
                    'label' => __('Lot').' '.$selectedId,
                ];
            }
        }

        return $options;
    }

    /**
     * @param  array<int, array{value: string, label: string}>  $options
     */
    protected static function containsValue(array $options, string $value): bool
    {
        foreach ($options as $option) {
            if ($option['value'] === $value) {
                return true;
            }
        }

        return false;
    }
}
