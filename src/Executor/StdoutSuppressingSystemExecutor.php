<?php
namespace Clue\Confgen\Executor;

/**
 * This Executor can be used to execute external commands/programs.
 *
 * Output to stdout will be suppressed
 *
 * Returns exit code of executed command line
 */
class StdoutSuppressingSystemExecutor implements ExecutorInterface
{
    public function executeCommand($cmd)
    {
        ob_start();
        passthru($cmd, $code);
        ob_end_clean();

        return $code;
    }
}
