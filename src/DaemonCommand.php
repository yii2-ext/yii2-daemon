<?php
/**
 * @author Alexey Samoylov <alexey.samoylov@gmail.com>
 */
namespace YarCode\Yii2\Daemon;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use yii\console\Controller;
use yii\console\ExitCode;

abstract class DaemonCommand extends Controller
{
    /** @var LoopInterface|null */
    protected $loop;

    public function init()
    {
        parent::init();
    }

    public function actionDaemon()
    {
        $loop = $this->getLoop();

        $this->prepare();
        $this->registerSignalHandlers($loop);

        $loop->run();
        $this->afterDaemon();

        return ExitCode::OK;
    }

    abstract public function prepare();

    protected function getLoop(): LoopInterface
    {
        if (!$this->loop instanceof LoopInterface) {
            $this->loop = Loop::get();
        }

        return $this->loop;
    }

    protected function afterDaemon()
    {
    }

    protected function beforeStop()
    {
    }

    private function registerSignalHandlers(LoopInterface $loop)
    {
        if (!extension_loaded('pcntl') || !function_exists('pcntl_signal')) {
            return;
        }

        foreach ([SIGINT, SIGTERM] as $signal) {
            $loop->addSignal($signal, function () use ($loop) {
                $this->beforeStop();
                $loop->stop();
            });
        }
    }
}
