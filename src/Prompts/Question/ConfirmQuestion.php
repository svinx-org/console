<?php

namespace Svinx\Console\Prompts\Question;

use Svinx\Console\Prompts\Colors;

class ConfirmQuestion implements Question
{
    public function __construct(
        private string $message,
        private bool $default = true,
    ) {
    }

    public function ask(): bool
    {
        $hint = $this->default ? 'O/n' : 'o/N';

        echo Colors::paint('?', 'green') . ' ' . $this->message . ' '
            . Colors::paint("({$hint})", 'gray') . ' ';

        $line = fgets(STDIN);
        $value = strtolower(trim($line === false ? '' : $line));

        if ($value === '') {
            return $this->default;
        }

        return in_array($value, ['o', 'oui', 'y', 'yes'], true);
    }
}
