<?php

namespace App\Support;

use App\Models\User;
use Filament\Support\Colors\Color;

class ThemeManager
{
    public const DEFAULT_THEME = 'modern';

    /**
     * Metadata and Filament color palettes for each built-in admin theme preset.
     *
     * @return array<string, array{label: string, description: string, colors: array<string, mixed>}>
     */
    public static function presets(): array
    {
        return [
            'modern' => [
                'label' => 'Modern SaaS (Indigo & Slate)',
                'description' => 'Crisp slate canvas, floating glass topbar, elevated cards, and modern indigo accents.',
                'colors' => [
                    'primary' => Color::Indigo,
                    'gray' => Color::Slate,
                ],
            ],
            'soft' => [
                'label' => 'Soft (Sage & Cream)',
                'description' => 'Warm cream canvas, Lora serif headings, sage accents, and soft elevated surfaces.',
                'colors' => [
                    'primary' => [
                        50 => '#f3f7f4',
                        100 => '#e2ece5',
                        200 => '#c6dacc',
                        300 => '#9ec0aa',
                        400 => '#719f82',
                        500 => '#4e8162',
                        600 => '#3a664b',
                        700 => '#2f523d',
                        800 => '#274232',
                        900 => '#21372b',
                        950 => '#111e17',
                    ],
                    'gray' => Color::Stone,
                ],
            ],
            'rose' => [
                'label' => 'Rose (Modern Blush)',
                'description' => 'Vibrant rose accents with cool zinc surfaces and translucent navigation.',
                'colors' => [
                    'primary' => Color::Rose,
                    'gray' => Color::Zinc,
                ],
            ],
            'noir' => [
                'label' => 'Noir (Warm Amber & Charcoal)',
                'description' => 'High-contrast neutral tones with warm amber highlights.',
                'colors' => [
                    'primary' => Color::Amber,
                    'gray' => Color::Neutral,
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(static::presets())
            ->mapWithKeys(fn (array $preset, string $key): array => [$key => $preset['label']])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return collect(static::presets())
            ->mapWithKeys(fn (array $preset, string $key): array => [$key => $preset['description']])
            ->all();
    }

    public static function resolveForUser(?User $user): string
    {
        $theme = $user?->theme;

        if (is_string($theme) && array_key_exists($theme, static::presets())) {
            return $theme;
        }

        return self::DEFAULT_THEME;
    }

    /**
     * @return array<string, mixed>
     */
    public static function colorsFor(string $theme): array
    {
        $presets = static::presets();

        return $presets[$theme]['colors'] ?? $presets[self::DEFAULT_THEME]['colors'];
    }
}
