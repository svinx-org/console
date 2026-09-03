<?php

namespace Svinx\Console\Prompts\Question;

use Svinx\Console\Prompts\Colors;

class NumberQuestion implements Question
{
    public function __construct(
        private string $message,
        private int|float|null $default = null,
        private int|float|null $min = null,
        private int|float|null $max = null,
    ) {
    }

    public function ask(): int|float
    {
        while (true) {
            $suffix = $this->default !== null
                ? ' ' . Colors::paint("({$this->default})", 'gray')
                : '';

            echo Colors::paint('?', 'green') . ' ' . $this->message . $suffix . ' ';

            $line = fgets(STDIN);
            $raw = trim($line === false ? '' : $line);

            if ($raw === '' && $this->default !== null) {
                return $this->default;
            }

            if (!is_numeric($raw)) {
                echo Colors::paint('  ✖ Merci d\'entrer un nombre valide.', 'red') . "\n";
                continue;
            }

            $value = str_contains($raw, '.') ? (float) $raw : (int) $raw;

            if ($this->min !== null && $value < $this->min) {
                echo Colors::paint("  ✖ La valeur minimale est {$this->min}.", 'red') . "\n";
                continue;
            }

            if ($this->max !== null && $value > $this->max) {
                echo Colors::paint("  ✖ La valeur maximale est {$this->max}.", 'red') . "\n";
                continue;
            }

            return $value;
        }
    }
}
