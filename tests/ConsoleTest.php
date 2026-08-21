<?php
declare(strict_types=1);

namespace Svinx\Console\Tests;

use Svinx\Console\Console;
use PHPUnit\Framework\TestCase;

final class ConsoleTest extends TestCase
{
    public function testCommandCanParseLongOption(): void
    {
        $received = null;
        $app = new Console();
        $app->command('serve')
            ->option('-p, --port <port>', 'Port', '8000')
            ->action(function ($input) use (&$received): int {
                $received = $input->option('port');
                return 0;
            });

        self::assertSame(0, $app->parse(['serve', '--port', '9000']));
        self::assertSame('9000', $received);
    }

    public function testCommandCanParseShortOption(): void
    {
        $received = null;
        $app = new Console();
        $app->command('serve')
            ->option('-p, --port <port>', 'Port', '8000')
            ->action(function ($input) use (&$received): int {
                $received = $input->option('port');
                return 0;
            });

        self::assertSame(0, $app->parse(['serve', '-p', '9001']));
        self::assertSame('9001', $received);
    }

    public function testBooleanOption(): void
    {
        $received = null;
        $app = new Console();
        $app->command('test')
            ->option('-u, --upper', 'Upper')
            ->action(function ($input) use (&$received): int {
                $received = $input->option('upper');
                return 0;
            });

        self::assertSame(0, $app->parse(['test', '--upper']));
        self::assertTrue($received);
    }

    public function testPositionalArgument(): void
    {
        $received = null;
        $app = new Console();
        $app->command('hello <name>')
            ->action(function ($input) use (&$received): int {
                $received = $input->argument('name');
                return 0;
            });

        self::assertSame(0, $app->parse(['hello', 'Alice']));
        self::assertSame('Alice', $received);
    }

    public function testDefaultOption(): void
    {
        $received = null;
        $app = new Console();
        $app->command('serve')
            ->option('-p, --port <port>', 'Port', '8000')
            ->action(function ($input) use (&$received): int {
                $received = $input->option('port');
                return 0;
            });

        self::assertSame(0, $app->parse(['serve']));
        self::assertSame('8000', $received);
    }

    public function testUnknownCommandReturnsErrorCode(): void
    {
        $app = new Console();
        self::assertSame(1, $app->parse(['missing']));
    }

    public function testMissingRequiredArgumentReturnsErrorCode(): void
    {
        $app = new Console();
        $app->command('hello <name>')
            ->action(fn () => 0);

        self::assertSame(1, $app->parse(['hello']));
    }

    public function testHelpDoesNotCrash(): void
    {
        $app = new Console();

        $app
            ->name('eorbah')
            ->version('0.1.0');

        $app
            ->command('serve')
            ->description('Start development server')
            ->action(fn () => 0);

        self::assertSame(0, $app->parse([]));
    }


    public function testCommandSpecRegistersNameSeparatelyFromArguments(): void
    {
        $received = null;

        $app = new Console();
        $app->command('hello <name>')
            ->action(function ($input) use (&$received): int {
                $received = $input->argument('name');
                return 0;
            });

        self::assertArrayHasKey('hello', $app->commands());
        self::assertSame(0, $app->parse(['hello', 'Bob']));
        self::assertSame('Bob', $received);
    }

    public function testMultipleCommandsCanBeExecuted(): void
    {
        $called = [];

        $app = new Console();

        $app->command('first')
            ->action(function () use (&$called): int {
                $called[] = 'first';
                return 0;
            });

        $app->command('second')
            ->action(function () use (&$called): int {
                $called[] = 'second';
                return 0;
            });

        self::assertSame(0, $app->parse(['first']));
        self::assertSame(0, $app->parse(['second']));
        self::assertSame(['first', 'second'], $called);
    }


    public function testCommandSpecUsesDeclaredNameProperty(): void
    {
        $app = new Console();

        $app->command('hello <name>')
            ->description('Test positional arguments')
            ->action(fn () => 0);

        self::assertSame('hello', $app->commands()['hello']->name());
        self::assertArrayNotHasKey('hello <name>', $app->commands());
    }


    public function testHelpCanBeUsedWithoutRequiredArgument(): void
    {
        $app = new Console();

        $app->command('hello <name>')
            ->description('Test positional arguments')
            ->action(fn () => 0);

        self::assertSame(0, $app->parse(['hello', '-h']));
        self::assertSame(0, $app->parse(['hello', '--help']));
        self::assertSame(0, $app->parse(['hello', 'n', '-h']));
    }

}