# yii2-ext/yii2-daemon

Yii2 console daemon built on the ReactPHP event loop.

Requires PHP 8.1+, Yii2 2.0.45+ and `react/event-loop` 1.6+.

## Installation

```bash
composer require yii2-ext/yii2-daemon
```

The extension ships only the event loop component. If your daemon also needs
other ReactPHP components, require them explicitly (for example
`composer require react/promise`).

## Usage

Extend `DaemonCommand`, implement `prepare()` and register your timers there:

```php
<?php

namespace console\controllers;

use YarCode\Yii2\Daemon\DaemonCommand;

class AsyncController extends DaemonCommand
{
    public function prepare()
    {
        $this->loop->addPeriodicTimer(1, function () {
            \Yii::$app->db->createCommand('SELECT 1')->execute();
        });

        $this->loop->addPeriodicTimer(0.1, function () {
            while ($task = \Yii::$app->async->receiveTask('search')) {
                if ($task->execute()) {
                    \Yii::$app->async->acknowledgeTask($task);
                }
            }
        });
    }

    protected function beforeStop()
    {
        // Close external connections before the loop exits.
    }
}
```

Run it as a regular Yii console command:

```bash
php yii async/daemon
```

### Lifecycle

1. The event loop is created lazily on the first `getLoop()` call (or when the
   `daemon` action starts), so constructing the controller has no side effects.
2. `prepare()` is called with `$this->loop` already initialized.
3. `SIGINT` and `SIGTERM` are handled when `ext-pcntl` is available: `beforeStop()`
   runs and the loop is stopped.
4. `afterDaemon()` runs after the loop exits. `actionDaemon()` returns
   `yii\console\ExitCode::OK`.

Long-running Yii processes should refresh stateful components (database
connections in particular) inside their timers — Yii does not do this
automatically.

## Development

```bash
composer update
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

## Upgrading from 0.3.x

The package name changed from `yarcode/yii2-daemon` to
`yii2-ext/yii2-daemon`. The API of `DaemonCommand` is unchanged, but the
dependency on the abandoned `react/react` 0.4 metapackage was replaced with
`react/event-loop` ^1.6, and PHP 8.1 is now the minimum version.

## License

MIT. See [LICENSE](LICENSE).
