<?php
declare(strict_types=1);

namespace Svinx\Console;

use InvalidArgumentException;
use Throwable;

final class Command
{
    private string $name;
    private ?string $description = null;
    private ?string $summary = null;
    private ?string $usage = null;
    private ?string $group = null;
    private $action = null;
    private array $arguments = [];
    private array $options = [];
    private array $commands = [];
    private array $aliases = [];
    private array $beforeHooks = [];
    private array $afterHooks = [];
    private bool $hidden = false;
    private bool $allowUnknownOptions = false;
    private bool $allowExcessArguments = false;

    public function __construct(string $spec)
    {
        $parts = preg_split('/\s+/', trim($spec), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $this->name = $parts[0] ?? '';
        foreach (array_slice($parts, 1) as $specPart) {
            $this->addArgumentSpec($specPart);
        }
    }

    public function name(?string $name = null): self|string
    {
        if ($name === null) return $this->name;
        $this->name = $name;
        return $this;
    }

    public function description(?string $description = null): self|string|null
    {
        if ($description === null) return $this->description;
        $this->description = $description;
        return $this;
    }

    public function summary(?string $summary = null): self|string|null
    {
        if ($summary === null) return $this->summary;
        $this->summary = $summary;
        return $this;
    }

    public function usage(?string $usage = null): self|string|null
    {
        if ($usage === null) return $this->usage;
        $this->usage = $usage;
        return $this;
    }

    public function group(string $group): self { $this->group = $group; return $this; }
    public function hidden(bool $hidden = true): self { $this->hidden = $hidden; return $this; }
    public function isHidden(): bool { return $this->hidden; }
    public function getDescription(): ?string { return $this->description; }
    public function getSummary(): ?string { return $this->summary; }
    public function getGroup(): ?string { return $this->group; }

    public function alias(string ...$aliases): self
    {
        foreach ($aliases as $alias) $this->aliases[] = $alias;
        return $this;
    }

    public function aliases(): array { return $this->aliases; }

    public function argument(string $spec, ?string $description = null, mixed $default = null): self
    {
        $parsed = self::parseArgumentSpec($spec);
        $this->arguments[] = new Argument(
            $parsed['name'], $parsed['required'], $description,
            $parsed['variadic'], $default
        );
        return $this;
    }

    public function arguments(string $specs): self
    {
        foreach (preg_split('/\s+/', trim($specs), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $spec) {
            $this->addArgumentSpec($spec);
        }
        return $this;
    }

    public function option(
        string $spec,
        ?string $description = null,
        mixed $default = null,
        ?callable $processor = null
    ): self {
        $parsed = self::parseOptionSpec($spec);
        $this->options[] = new Option(
            $parsed['short'], $parsed['long'], $parsed['valueName'], $default,
            $description, false, $parsed['variadic'], $parsed['negatable'],
            null, $processor
        );
        return $this;
    }

    public function requiredOption(
        string $spec,
        ?string $description = null,
        mixed $default = null,
        ?callable $processor = null
    ): self {
        $parsed = self::parseOptionSpec($spec);
        $this->options[] = new Option(
            $parsed['short'], $parsed['long'], $parsed['valueName'], $default,
            $description, true, $parsed['variadic'], $parsed['negatable'],
            null, $processor
        );
        return $this;
    }

    public function choices(string $name, array $choices): self
    {
        foreach ($this->options as $i => $option) {
            if ($option->long === ltrim($name, '-')) {
                $this->options[$i] = new Option(
                    $option->short, $option->long, $option->valueName, $option->default,
                    $option->description, $option->required, $option->variadic,
                    $option->negatable, $choices, $option->processor
                );
                return $this;
            }
        }
        foreach ($this->arguments as $i => $argument) {
            if ($argument->name === ltrim($name, '<>[]')) {
                $this->arguments[$i] = new Argument(
                    $argument->name, $argument->required, $argument->description,
                    $argument->variadic, $argument->default, $choices
                );
                return $this;
            }
        }
        throw new InvalidArgumentException("Unknown argument or option: {$name}");
    }

    public function command(string $spec): self
    {
        $command = new self($spec);
        $this->commands[$command->name()] = $command;
        return $command;
    }

    public function addCommand(Command $command): self
    {
        $this->commands[$command->name()] = $command;
        return $this;
    }

    public function commands(): array { return $this->commands; }
    public function argumentsList(): array { return $this->arguments; }
    public function options(): array { return $this->options; }

    public function before(callable $hook): self { $this->beforeHooks[] = $hook; return $this; }
    public function after(callable $hook): self { $this->afterHooks[] = $hook; return $this; }

    public function allowUnknownOption(bool $allow = true): self { $this->allowUnknownOptions = $allow; return $this; }
    public function allowExcessArguments(bool $allow = true): self { $this->allowExcessArguments = $allow; return $this; }
    public function allowsExcessArguments(): bool { return $this->allowExcessArguments; }

    public function action(callable $action): self { $this->action = $action; return $this; }

    public function run(array $argv, Output $output): int
    {
        if ($this->commands) {
            $first = $argv[0] ?? null;
            if ($first !== null && isset($this->commands[$first])) {
                $child = array_shift($argv);
                return $this->commands[$child]->run($argv, $output);
            }
        }

        [$input, $help] = Parser::parseCommand($this, $argv);

        if ($help) {
            $output->writeln($this->help());
            return 0;
        }

        foreach ($this->beforeHooks as $hook) $hook($input, $output);

        if ($this->action === null) {
            throw new InvalidArgumentException("Command '{$this->name}' has no action.");
        }

        $result = ($this->action)($input, $output);
        $exitCode = is_int($result) ? $result : 0;

        foreach ($this->afterHooks as $hook) $hook($input, $output, $exitCode);
        return $exitCode;
    }

    public function help(): string
    {
        $usage = $this->usage ?? $this->name;
        foreach ($this->arguments as $arg) {
            $usage .= ' ' . ($arg->required ? '<' . $arg->name . ($arg->variadic ? '...' : '') . '>' : '[' . $arg->name . ($arg->variadic ? '...' : '') . ']');
        }

        $out = ["Usage: {$usage}", ''];
        if ($this->description) { $out[] = $this->description; $out[] = ''; }

        if ($this->commands) {
            $out[] = 'Commands:';
            foreach ($this->commands as $cmd) {
                if (!$cmd->isHidden()) $out[] = sprintf('  %-24s %s', $cmd->name(), $cmd->getSummary() ?? $cmd->getDescription() ?? '');
            }
            $out[] = '';
        }

        if ($this->arguments) {
            $out[] = 'Arguments:';
            foreach ($this->arguments as $arg) $out[] = sprintf('  %-20s %s', $arg->name, $arg->description ?? '');
            $out[] = '';
        }

        $out[] = 'Options:';
        $out[] = '  -h, --help             Show help';
        foreach ($this->options as $option) {
            $spec = ($option->short ? $option->short . ', ' : '') . '--' . $option->long;
            if ($option->valueName) $spec .= ' <' . $option->valueName . ($option->variadic ? '...' : '') . '>';
            if ($option->negatable) $spec = ($option->short ? $option->short . ', ' : '') . '--no-' . $option->long;
            $out[] = sprintf('  %-22s %s', $spec, $option->description ?? '');
        }

        return implode(PHP_EOL, $out) . PHP_EOL;
    }

    private function addArgumentSpec(string $spec): void
    {
        $parsed = self::parseArgumentSpec($spec);
        $this->arguments[] = new Argument($parsed['name'], $parsed['required'], null, $parsed['variadic']);
    }

    private static function parseArgumentSpec(string $spec): array
    {
        if (preg_match('/^<([A-Za-z_][A-Za-z0-9_-]*)(\.\.\.)?>$/', $spec, $m))
            return ['name'=>$m[1], 'required'=>true, 'variadic'=>isset($m[2]) && $m[2] !== ''];
        if (preg_match('/^\[([A-Za-z_][A-Za-z0-9_-]*)(\.\.\.)?\]$/', $spec, $m))
            return ['name'=>$m[1], 'required'=>false, 'variadic'=>isset($m[2]) && $m[2] !== ''];
        throw new InvalidArgumentException("Invalid argument specification: {$spec}");
    }

    private static function parseOptionSpec(string $spec): array
    {
        $short = null; $long = null; $valueName = null; $variadic = false; $negatable = false;
        foreach (preg_split('/\s*,\s*/', trim($spec), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            if (preg_match('/^-([A-Za-z0-9])$/', $part, $m)) { $short = '-' . $m[1]; continue; }
            if (preg_match('/^--no-([A-Za-z0-9][A-Za-z0-9_-]*)$/', $part, $m)) { $long=$m[1]; $negatable=true; continue; }
            if (preg_match('/^--([A-Za-z0-9][A-Za-z0-9_-]*)(?:\s+<([^>]+)>)?$/', $part, $m)) {
                $long=$m[1]; $valueName=$m[2]??null;
                if ($valueName !== null && str_ends_with($valueName, '...')) { $variadic=true; $valueName=substr($valueName,0,-3); }
                continue;
            }
            throw new InvalidArgumentException("Invalid option specification: {$part}");
        }
        if ($long === null) throw new InvalidArgumentException("Option requires a long name: {$spec}");
        return compact('short','long','valueName','variadic','negatable');
    }
}
