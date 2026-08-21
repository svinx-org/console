<?php
declare(strict_types=1);

namespace Svinx\Console;

final class Input
{
    public function __construct(
        private readonly array $arguments,
        private readonly array $options,
        private readonly array $remaining = [],
    ) {}

    public function argument(string $name, mixed $default = null): mixed
    {
        return $this->arguments[$name] ?? $default;
    }

    public function option(string $name, mixed $default = null): mixed
    {
        $key = ltrim($name, '-');
        return $this->options[$key] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists(ltrim($name, '-'), $this->options);
    }

    public function arguments(): array { return $this->arguments; }
    public function options(): array { return $this->options; }
    public function remaining(): array { return $this->remaining; }
}
