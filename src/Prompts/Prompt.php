<?php

namespace Svinx\Console\Prompts;

use Svinx\Console\Prompts\Question\ConfirmQuestion;
use Svinx\Console\Prompts\Question\MultiSelectQuestion;
use Svinx\Console\Prompts\Question\NumberQuestion;
use Svinx\Console\Prompts\Question\PasswordQuestion;
use Svinx\Console\Prompts\Question\SelectQuestion;
use Svinx\Console\Prompts\Question\TextQuestion;

/**
 * Façade façon Inquirer.js pour poser des questions interactives dans
 * le terminal.
 *
 * Exemple :
 *   $name = Prompt::text('Nom du projet ?', default: 'my-app');
 *   $useGit = Prompt::confirm('Initialiser Git ?');
 *   $pm = Prompt::select('Gestionnaire de paquets ?', ['npm', 'yarn', 'pnpm']);
 *
 * Ou en enchaînant plusieurs questions d'un coup :
 *   $answers = Prompt::ask([
 *       ['type' => 'text', 'name' => 'appName', 'message' => 'Nom du projet ?'],
 *       ['type' => 'confirm', 'name' => 'useGit', 'message' => 'Initialiser Git ?', 'default' => true],
 *   ]);
 */
class Prompt
{
    public static function text(
        string $message,
        ?string $default = null,
        ?callable $validate = null,
        ?callable $filter = null,
    ): string {
        return (new TextQuestion($message, $default, $validate, $filter))->ask();
    }

    public static function password(
        string $message,
        string $mask = '*',
        ?callable $validate = null,
    ): string {
        return (new PasswordQuestion($message, $mask, $validate))->ask();
    }

    public static function confirm(string $message, bool $default = true): bool
    {
        return (new ConfirmQuestion($message, $default))->ask();
    }

    public static function number(
        string $message,
        int|float|null $default = null,
        int|float|null $min = null,
        int|float|null $max = null,
    ): int|float {
        return (new NumberQuestion($message, $default, $min, $max))->ask();
    }

    /**
     * @param array<int, string|array{label: string, value?: mixed}> $choices
     */
    public static function select(string $message, array $choices): mixed
    {
        return (new SelectQuestion($message, $choices))->ask();
    }

    /**
     * @param array<int, string|array{label: string, value?: mixed, checked?: bool}> $choices
     */
    public static function multiselect(string $message, array $choices): array
    {
        return (new MultiSelectQuestion($message, $choices))->ask();
    }

    /**
     * Enchaîne plusieurs questions décrites sous forme de tableau et
     * retourne les réponses indexées par "name" (comme `inquirer.prompt`).
     *
     * @param array<int, array<string, mixed>> $questions
     * @return array<string, mixed>
     */
    public static function ask(array $questions): array
    {
        $answers = [];

        foreach ($questions as $q) {
            $type = $q['type'] ?? 'text';
            $name = $q['name'] ?? throw new \InvalidArgumentException('Chaque question doit avoir un "name".');
            $message = $q['message'] ?? $name;

            $answers[$name] = match ($type) {
                'text' => self::text($message, $q['default'] ?? null, $q['validate'] ?? null, $q['filter'] ?? null),
                'password' => self::password($message, $q['mask'] ?? '*', $q['validate'] ?? null),
                'confirm' => self::confirm($message, $q['default'] ?? true),
                'number' => self::number($message, $q['default'] ?? null, $q['min'] ?? null, $q['max'] ?? null),
                'select' => self::select($message, $q['choices'] ?? []),
                'multiselect' => self::multiselect($message, $q['choices'] ?? []),
                default => throw new \InvalidArgumentException("Type de question inconnu : \"{$type}\"."),
            };
        }

        return $answers;
    }
}
