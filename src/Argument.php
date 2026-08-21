<?php
declare(strict_types=1);

namespace Svinx\Console;

use InvalidArgumentException;

final class Argument
{
    public function __construct(
        public readonly string $name,
        public readonly bool $required = true,
        public readonly ?string $description = null,
        public readonly bool $variadic = false,
        public readonly mixed $default = null,
        public readonly ?array $choices = null,
    ) {}

    public function validate(mixed $value): mixed
    {
        if ($this->choices !== null && $value !== null) {
            $values = $this->variadic && is_array($value) ? $value : [$value];
            foreach ($values as $item) {
                if (!in_array($item, $this->choices, true)) {
                    throw new InvalidArgumentException(
                        "Invalid value for argument '{$this->name}': {$item}. Expected one of: " .
                        implode(', ', $this->choices)
                    );
                }
            }
        }
        return $value;
    }
}
