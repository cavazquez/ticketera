<?php

declare(strict_types=1);

namespace App\Support;

use BackedEnum;

class EnumOptions
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return list<array{value: string, label: string, color?: string}>
     */
    public static function from(string $enumClass, bool $withColor = false): array
    {
        return array_map(function (BackedEnum $case) use ($withColor): array {
            $option = [
                'value' => (string) $case->value,
                'label' => method_exists($case, 'label') ? (string) $case->label() : $case->name,
            ];

            if ($withColor && method_exists($case, 'color')) {
                $option['color'] = (string) $case->color();
            }

            return $option;
        }, $enumClass::cases());
    }
}
