<?php

use Clue\Confgen\Executor\StdoutSuppressingSystemExecutor;

class StdoutSuppressingSystemExecutorTest extends TestCase
{
    private $executor;

    public function setUp()
    {
        $this->executor = new StdoutSuppressingSystemExecutor();
    }

    public function testExecuteOKNoOutput()
    {
        $this->expectOutputString('');
        $code = $this->executor->executeCommand('echo Hey');
        $this->assertEquals(0, $code);
    }

    public function testNoStdoutOutputWhenExecutedCommandPipedToStdError()
    {
        $this->expectOutputString('');
        $this->executor->executeCommand('echo -n Hey >&2');
    }

    public function testExecuteFail()
    {
        $code = $this->executor->executeCommand('echo Hey && exit 1');
        $this->assertEquals(1, $code);
    }
}
