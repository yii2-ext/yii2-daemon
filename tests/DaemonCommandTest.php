<?php

namespace YarCode\Yii2\Daemon\Tests;

use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use ReflectionProperty;
use YarCode\Yii2\Daemon\DaemonCommand;
use yii\console\Application;
use yii\console\ExitCode;

final class DaemonCommandTest extends TestCase
{
    public function testPrepareIsCalledBeforeRunAndSuccessIsReturned(): void
    {
        $calls = [];
        $loop = $this->createMock(LoopInterface::class);
        $loop->expects(self::once())
            ->method('run')
            ->willReturnCallback(static function () use (&$calls): void {
                $calls[] = 'run';
            });

        $command = new TestDaemonCommand('daemon', $this->app());
        $command->prepareCallback = static function () use (&$calls): void {
            $calls[] = 'prepare';
        };
        $this->injectLoop($command, $loop);

        self::assertSame(ExitCode::OK, $command->actionDaemon());
        self::assertSame(['prepare', 'run'], $calls);
    }

    public function testAfterHookIsCalledAfterRun(): void
    {
        $calls = [];
        $loop = $this->createMock(LoopInterface::class);
        $loop->method('run')->willReturnCallback(static function () use (&$calls): void {
            $calls[] = 'run';
        });

        $command = new TestDaemonCommand('daemon', $this->app());
        $command->afterCallback = static function () use (&$calls): void {
            $calls[] = 'after';
        };
        $this->injectLoop($command, $loop);

        $command->actionDaemon();

        self::assertSame(['run', 'after'], $calls);
    }

    public function testPrepareReceivesConfiguredLoop(): void
    {
        $loop = $this->createMock(LoopInterface::class);
        $command = new TestDaemonCommand('daemon', $this->app());
        $seen = null;
        $command->prepareCallback = static function (TestDaemonCommand $command) use (&$seen): void {
            $seen = $command->getPublicLoop();
        };
        $this->injectLoop($command, $loop);

        $command->actionDaemon();

        self::assertSame($loop, $seen);
    }

    public function testLoopIsCreatedLazilyAndReused(): void
    {
        $command = new TestDaemonCommand('daemon', $this->app());

        $first = $command->getPublicLoop();

        self::assertInstanceOf(LoopInterface::class, $first);
        self::assertSame($first, $command->getPublicLoop());
    }

    public function testSignalsStopTheLoop(): void
    {
        if (!extension_loaded('pcntl')) {
            self::markTestSkipped('ext-pcntl is required to handle SIGINT/SIGTERM.');
        }

        $listeners = [];
        $loop = $this->createMock(LoopInterface::class);
        $loop->method('addSignal')->willReturnCallback(
            static function (int $signal, callable $listener) use (&$listeners): void {
                $listeners[$signal] = $listener;
            }
        );
        $loop->expects(self::once())->method('stop');

        $command = new TestDaemonCommand('daemon', $this->app());
        $this->injectLoop($command, $loop);

        $command->actionDaemon();

        self::assertArrayHasKey(SIGINT, $listeners);
        self::assertArrayHasKey(SIGTERM, $listeners);

        $listeners[SIGTERM]();
    }

    private function app(): Application
    {
        return new Application([
            'id' => 'daemon-test',
            'basePath' => dirname(__DIR__),
        ]);
    }

    private function injectLoop(DaemonCommand $command, LoopInterface $loop): void
    {
        $property = new ReflectionProperty(DaemonCommand::class, 'loop');
        $property->setValue($command, $loop);
    }
}

final class TestDaemonCommand extends DaemonCommand
{
    /** @var callable|null */
    public $prepareCallback;

    /** @var callable|null */
    public $afterCallback;

    public function prepare()
    {
        if ($this->prepareCallback !== null) {
            ($this->prepareCallback)($this);
        }
    }

    protected function afterDaemon()
    {
        if ($this->afterCallback !== null) {
            ($this->afterCallback)($this);
        }
    }

    public function getPublicLoop(): LoopInterface
    {
        return $this->getLoop();
    }
}
