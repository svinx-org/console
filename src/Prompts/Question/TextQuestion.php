<?php

namespace Svinx\Console\Prompts\Question;

use Svinx\Console\Prompts\Colors;

class TextQuestion implements Question
{
    /**
     * @param callable|null $validate function(string $value): bool|string
     *                                 Retourner `true` si valide, sinon un
     *                                 message d'erreur (ou `false`).
     * @param callable|null $filter   function(string $value): string
     *                                 Transforme la valeur avant retour.
     */
    public function __construct(
        private string $message,
        private ?string $default = null,
        private $validate = null,
        private $filter = null,
    ) {
    }

    public function ask(): string
    {
        while (true) {
            $suffix = $this->default !== null
                ? ' ' . Colors::paint("({$this->default})", 'gray')
                : '';

            echo Colors::paint('?', 'green') . ' ' . $this->message . $suffix . ' ';

            $line = fgets(STDIN);
            $value = trim($line === false ? '' : $line);

            if ($value === '' && $this->default !== null) {
                $value = $this->default;
            }

            if ($this->filter !== null) {
                $value = ($this->filter)($value);
            }

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
}
