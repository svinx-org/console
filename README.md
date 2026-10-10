# Eorbah545 Console

A small, fluent PHP CLI framework inspired by Commander.js.

## Requirements

- PHP 8.1+
- Composer

## Example

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Svinx\Console\Console;

$program = new Console();

$program
    ->name('eorbah')
    ->description('EorbahAPI CLI')
    ->version('0.1.0');

$program
    ->command('serve')
    ->description('Start development server')
    ->option('-p, --port <port>', 'Server port', '8000')
    ->action(function ($input, $output) {
        $output->success(
            'Server: ' .
            $input->option('port')
        );
    });

$program->parse();
```

## Current MVP

- Fluent command registration
- Required positional arguments: `<name>`
- Optional positional arguments: `[name]`
- Long options: `--port <port>`
- Short options: `-p`
- Boolean options
- Default option values
- `--help` / `-h`
- `--version` / `-V`
- Basic colored output
- PHPUnit tests

This is intentionally a small foundation. It is not intended to compete feature-for-feature with Symfony Console yet.
