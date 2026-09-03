<?php

namespace Svinx\Console\Prompts\Question;

use Svinx\Console\Prompts\Colors;
use Svinx\Console\Prompts\Terminal;

class PasswordQuestion implements Question
{
    public function __construct(
        private string $message,
        private string $mask = '*',
        private $validate = null,
    ) {
    }

    public function ask(): string
    {
        while (true) {
            echo Colors::paint('?', 'green') . ' ' . $this->message . ' ';

            $value = $this->readMasked();
            echo "\n";

            if ($this->validate !== null) {
                $result = ($this->validate)($value);
                if ($result !== true) {
                    $message = is_string($result) ? $result : 'Valeur invalide.';
                    echo Colors::paint('  ✖ ' . $message, 'red') . "\n";
                    continue;
                }
            }

            return $value;
        }
    }

    private function readMasked(): string
    {
        Terminal::enableRawMode();
        $value = '';

        try {
            while (true) {
                $key = Terminal::readKey();

                if ($key === 'enter' || $key === 'eof') {
                    break;
                }

                if ($key === 'ctrl_c') {
                    Terminal::reset();
                    exit(1);
                }

                if ($key === 'backspace') {
                    if ($value !== '') {
                        $value = mb_substr($value, 0, -1);
                        echo "\x08\033[K";
                    }
                    continue;
                }

                if ($key === 'space') {
                    $value .= ' ';
                    echo $this->mask;
                    continue;
                }

                if (mb_strlen($key) === 1) {
                    $value .= $key;
                    echo $this->mask;
                }
                // touches multi-caractères (up/down/esc...) : ignorées
            }
        } finally {
            Terminal::disableRawMode();
        }

        return $value;
    }
}
