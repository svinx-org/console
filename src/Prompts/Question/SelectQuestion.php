<?php

namespace Svinx\Console\Prompts\Question;

use Svinx\Console\Prompts\Colors;
use Svinx\Console\Prompts\Terminal;

class SelectQuestion implements Question
{
    private int $cursor = 0;

    /** @var array<int, array{label: string, value: mixed}> */
    private array $choices;

    /**
     * @param array<int, string|array{label: string, value?: mixed}> $choices
     */
    public function __construct(
        private string $message,
        array $choices,
    ) {
        if ($choices === []) {
            throw new \InvalidArgumentException('Une question "select" doit avoir au moins un choix.');
        }

        $this->choices = array_map(static function ($choice) {
            return is_array($choice)
                ? ['label' => $choice['label'], 'value' => $choice['value'] ?? $choice['label']]
                : ['label' => (string) $choice, 'value' => $choice];
        }, array_values($choices));
    }

    public function ask(): mixed
    {
        Terminal::hideCursor();
        Terminal::enableRawMode();

        try {
            echo Colors::paint('?', 'green') . ' ' . $this->message
                . ' ' . Colors::paint('(flèches pour naviguer, Entrée pour valider)', 'gray') . "\n";

            $this->render(first: true);

            while (true) {
                $key = Terminal::readKey();

                if ($key === 'up') {
                    $this->cursor = ($this->cursor - 1 + count($this->choices)) % count($this->choices);
                    $this->render();
                } elseif ($key === 'down') {
                    $this->cursor = ($this->cursor + 1) % count($this->choices);
                    $this->render();
                } elseif ($key === 'enter' || $key === 'eof') {
                    break;
                } elseif ($key === 'ctrl_c') {
                    Terminal::reset();
                    exit(1);
                }
            }
        } finally {
            Terminal::disableRawMode();
            Terminal::showCursor();
        }

        $selected = $this->choices[$this->cursor];
        echo Colors::paint('  ✔ ', 'green') . $selected['label'] . "\n";

        return $selected['value'];
    }

    private function render(bool $first = false): void
    {
        if (!$first) {
            Terminal::clearLinesUp(count($this->choices));
        }

        foreach ($this->choices as $i => $choice) {
            if ($i === $this->cursor) {
                echo Colors::paint('  ❯ ' . $choice['label'], 'cyan', 'bold') . "\n";
            } else {
                echo '    ' . $choice['label'] . "\n";
            }
        }
    }
}
