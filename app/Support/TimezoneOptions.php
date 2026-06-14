<?php

declare(strict_types=1);

namespace App\Support;

class TimezoneOptions
{
    /**
     * @return list<array{region: string, options: list<array{value: string, label: string}>}>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (\DateTimeZone::listIdentifiers() as $identifier) {
            [$region, $city] = array_pad(explode('/', $identifier, 2), 2, null);
            $grouped[$region][] = [
                'value' => $identifier,
                'label' => $city !== null ? str_replace('_', ' ', $city) : $identifier,
            ];
        }

        ksort($grouped);

        return array_values(collect($grouped)
            ->map(fn (array $options, string $region) => [
                'region' => $region,
                'options' => $options,
            ])
            ->values()
            ->all());
    }
}
