<?php

namespace Svinx\Console\Prompts;

/**
 * Utilitaire bas niveau pour piloter le terminal :
 * mode raw (lecture touche par touche), curseur, effacement de lignes.
 *
 * Toutes les classes du module Prompts s'appuient dessus, mais rien
 * n'empêche de l'utiliser directement pour construire d'autres widgets.
 */
class Terminal
{
    private static ?string $originalSttySettings = null;
    private static int $rawModeDepth = 0;

    public static function isInteractive(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return true;
        }

        return function_exists('posix_isatty') ? @posix_isatty(STDOUT) : true;
    }

    /**
     * Passe le terminal en mode "raw" : pas d'écho, pas de buffer ligne par ligne.
     * Réentrant : peut être appelé plusieurs fois imbriqués sans perdre le
     * réglage d'origine (utile quand un prompt en imbrique un autre).
     */
    public static function enableRawMode(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return; // Windows : pas de gestion stty ici, on reste en mode ligne
        }

        if (self::$rawModeDepth === 0) {
            self::$originalSttySettings = trim((string) shell_exec('stty -g 2>/dev/null'));
            shell_exec('stty -icanon -echo 2>/dev/null');
        }

        self::$rawModeDepth++;
    }

    public static function disableRawMode(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return;
        }

        if (self::$rawModeDepth === 0) {
            return;
        }

        self::$rawModeDepth--;

        if (self::$rawModeDepth === 0) {
            if (self::$originalSttySettings) {
                shell_exec('stty ' . self::$originalSttySettings . ' 2>/dev/null');
            } else {
                shell_exec('stty sane 2>/dev/null');
            }
        }
    }

    /**
     * Lit une "touche" logique depuis STDIN.
     *
     * Retourne soit un caractère simple, soit un nom symbolique pour les
     * touches spéciales : up, down, left, right, enter, backspace, space,
     * esc, ctrl_c.
     */
    public static function readKey(): string
    {
        $char = fread(STDIN, 1);

        if ($char === false || $char === '') {
            return 'eof';
        }

        if ($char === "\x1b") {
            $rest = fread(STDIN, 2);
            $seq = $char . $rest;

            return match ($seq) {
                "\x1b[A" => 'up',
                "\x1b[B" => 'down',
                "\x1b[C" => 'right',
                "\x1b[D" => 'left',
                default => 'esc',
            };
        }

        return match ($char) {
            "\n", "\r" => 'enter',
            "\x7f", "\x08" => 'backspace',
            "\x03" => 'ctrl_c',
            ' ' => 'space',
            default => $char,
        };
    }

    public static function hideCursor(): void
    {
        echo "\033[?25l";
    }

    public static function showCursor(): void
    {
        echo "\033[?25h";
    }

    public static function cursorUp(int $lines = 1): void
    {
        if ($lines > 0) {
            echo "\033[{$lines}A";
        }
    }

    public static function moveToColumn0(): void
    {
        echo "\r";
    }

    public static function clearLine(): void
    {
        echo "\033[2K";
    }

    /**
     * Efface les $n dernières lignes écrites (utilisé pour redessiner les
     * listes de choix des prompts select/multiselect sans faire défiler
     * le terminal).
     */
    public static function clearLinesUp(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            self::moveToColumn0();
            self::clearLine();
            if ($i < $n - 1) {
                self::cursorUp(1);
            }
        }
    }

    public static function width(): int
    {
        $cols = (int) trim((string) shell_exec('tput cols 2>/dev/null'));

        return $cols > 0 ? $cols : 80;
    }

    /**
     * Restaure proprement le terminal (curseur visible, mode ligne).
     * A appeler dans un handler de sortie / signal si besoin.
     */
    public static function reset(): void
    {
        self::disableRawMode();
        self::showCursor();
    }
}
