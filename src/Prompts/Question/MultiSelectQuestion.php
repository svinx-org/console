<?php

namespace Svinx\Console\Prompts\Question;

use Svinx\Console\Prompts\Colors;
use Svinx\Console\Prompts\Terminal;

class MultiSelectQuestion implements Question
{
    private int $cursor = 0;

    /** @var array<int, array{label: string, value: mixed, checked: bool}> */
    private array $choices;

    /**
     * @param array<int, string|array{label: string, value?: mixed, checked?: bool}> $choices
     */
    public function __construct(
        private string $message,
        array $choices,
    ) {
        if ($choices === []) {
            throw new \InvalidArgumentException('Une question "multiselect" doit avoir au moins un choix.');
        }

        $this->choices = array_map(static function ($choice) {
            return is_array($choice)
                ? [
                    'label' => $choice['label'],
                    'value' => $choice['value'] ?? $choice['label'],
                    'checked' => $choice['checked'] ?? false,
                ]
                : ['label' => (string) $choice, 'value' => $choice, 'checked' => false];
        }, array_values($choices));
    }

    public function ask(): array
    {
        Terminal::hideCursor();
        Terminal::enableRawMode();

        try {
            echo Colors::paint('?', 'green') . ' ' . $this->message
                . ' ' . Colors::paint('(espace pour cocher, Entrée pour valider)', 'gray') . "\n";

            $this->render(first: true);

            while (true) {
                $key = Terminal::readKey();

                if ($key === 'up') {
                    $this->cursor = ($this->cursor - 1 + count($this->choices)) % count($this->choices);
                    $this->render();
                } elseif ($key === 'down') {
                    $this->cursor = ($this->cursor + 1) % count($this->choices);
                    $this->render();
                } elseif ($key === 'space') {
                    $this->choices[$this->cursor]['checked'] = !$this->choices[$this->cursor]['checked'];
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

        $values = [];
        $labels = [];

        foreach ($this->choices as $choice) {
            if ($choice['checked']) {
                $values[] = $choice['value'];
                $labels[] = $choice['label'];
            }
        }

        echo Colors::paint('  ✔ ', 'green')
            . ($labels === [] ? Colors::paint('(aucun)', 'gray') : implode(', ', $labels))
            . "\n";

        return $values;
    }

    private function render(bool $first = false): void
    {
        if (!$first) {
            Terminal::clearLinesUp(count($this->choices));
        }

        foreach ($this->choices as $i => $choice) {
            $box = $choice['checked'] ? Colors::paint('◉', 'cyan') : '◯';
            $line = $box . ' ' . $choice['label'];

            if ($i === $this->cursor) {
                echo Colors::paint('  ❯ ' . $line, 'bold') . "\n";
            } else {
                echo '    ' . $line . "\n";
            }
        }
    }
}
