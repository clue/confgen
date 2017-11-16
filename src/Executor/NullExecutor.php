<?php
namespace Clue\Confgen\Executor;

/**
 * This Executor can be used to skip command line execution.
 *
 * If will always return 0 as exit code
 */
class NullExecutor implements ExecutorInterface
{
    public function executeCommand($cmd)
    {
        // noop
        return 0;
    }
}
