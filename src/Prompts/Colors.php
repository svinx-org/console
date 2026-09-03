<?php

namespace Svinx\Console\Prompts;

/**
 * Petit helper de coloration ANSI, indépendant de la classe Output du
 * framework Console pour que le module Prompts reste autonome.
 */
class Colors
{
    private const CODES = [
        'reset' => 0,
        'bold' => 1,
        'dim' => 2,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'white' => 37,
        'gray' => 90,
    ];

    public static function paint(string $text, string ...$styles): string
    {
        if ($text === '' || !self::supportsColor()) {
            return $text;
        }

        $codes = array_map(static fn (string $s) => self::CODES[$s] ?? 0, $styles);

        if ($codes === []) {
            return $text;
        }

        return "\033[" . implode(';', $codes) . "m{$text}\033[0m";
    }

    public static function supportsColor(): bool
    {
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        if (getenv('FORCE_COLOR') !== false) {
            return true;
        }

        return Terminal::isInteractive();
    }
}
