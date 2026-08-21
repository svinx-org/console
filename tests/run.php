<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Svinx\Console\Console;

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;

    try {
        $fn();
        echo "PASS  {$name}" . PHP_EOL;
        $passed++;
    } catch (Throwable $e) {
        echo "FAIL  {$name}: {$e->getMessage()}" . PHP_EOL;
        $failed++;
    }
}

function assertSame(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            'Expected ' . var_export($expected, true) .
            ', got ' . var_export($actual, true)
        );
    }
}

test('command signature parses required argument', function () {
    $cli = new Console();
    $cli->command('make:controller <name>')
        ->action(fn ($input) => $input->argument('name'));

    assertSame(0, $cli->parse(['eorbah', 'make:controller', 'UserController']));
});

test('long and short options parse', function () {
    $cli = new Console();
    $seen = null;

    $cli->command('serve')
        ->option('-p, --port <port>', 'Port', 8000)
        ->option('-H, --host <host>', 'Host', '127.0.0.1')
        ->action(function ($input) use (&$seen) {
            $seen = [$input->option('port'), $input->option('host')];
            return 0;
        });

    assertSame(0, $cli->parse(['eorbah', 'serve', '-p', '9000', '--host=0.0.0.0']));
    assertSame([9000, '0.0.0.0'], $seen);
});

test('boolean option works', function () {
    $cli = new Console();
    $seen = null;

    $cli->command('serve')
        ->option('--background', 'Background', false)
        ->action(function ($input) use (&$seen) {
            $seen = $input->option('background');
            return 0;
        });

    $cli->parse(['eorbah', 'serve', '--background']);
    assertSame(true, $seen);
});

test('default option works', function () {
    $cli = new Console();
    $seen = null;

    $cli->command('serve')
        ->option('-p, --port <port>', 'Port', 8000)
        ->action(function ($input) use (&$seen) {
            $seen = $input->option('port');
            return 0;
        });

    $cli->parse(['eorbah', 'serve']);
    assertSame(8000, $seen);
});

test('required argument failure returns 1', function () {
    $cli = new Console();
    $cli->command('make <name>')->action(fn () => 0);
    assertSame(1, $cli->parse(['eorbah', 'make']));
});

test('unknown command returns 1', function () {
    $cli = new Console();
    assertSame(1, $cli->parse(['eorbah', 'missing']));
});

test('action can return exit code', function () {
    $cli = new Console();
    $cli->command('fail')->action(fn () => 7);
    assertSame(7, $cli->parse(['eorbah', 'fail']));
});

echo PHP_EOL . "{$passed} passed, {$failed} failed" . PHP_EOL;
exit($failed === 0 ? 0 : 1);
