<?php
declare(strict_types=1);

namespace Svinx\Console;

use InvalidArgumentException;

final class Option
{
    public function __construct(
        public readonly ?string $short,
        public readonly string $long,
        public readonly ?string $valueName = null,
        public readonly mixed $default = null,
        public readonly ?string $description = null,
        public readonly bool $required = false,
        public readonly bool $variadic = false,
        public readonly bool $negatable = false,
        public readonly ?array $choices = null,
        public readonly mixed $processor = null,
    ) {}

    public function takesValue(): bool { return $this->valueName !== null; }

    public function process(mixed $value): mixed
    {
        if ($this->choices !== null && $value !== null) {
            $values = $this->variadic && is_array($value) ? $value : [$value];
            foreach ($values as $item) {
                if (!in_array($item, $this->choices, true)) {
                    throw new InvalidArgumentException(
                        "Invalid value for option '--{$this->long}': {$item}. Expected one of: " .
                        implode(', ', $this->choices)
                    );
                }
            }
        }
        if ($this->processor !== null) {
            return ($this->processor)($value);
        }
        return $value;
    }
}
