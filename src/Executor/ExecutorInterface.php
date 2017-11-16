<?php
namespace Clue\Confgen\Executor;

interface ExecutorInterface
{
    /**
     * Executes the given command line
     *
     * @param string $cmd Command line to execute
     * @return int Exit code of executed command line
     */
    public function executeCommand($cmd);
}
