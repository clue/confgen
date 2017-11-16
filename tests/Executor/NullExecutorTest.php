<?php
use Clue\Confgen\Executor\NullExecutor;

class NullExecutorTest extends TestCase
{
    private $executor;

    public function setUp()
    {
        $this->executor = new NullExecutor();
    }

    public function testCommandNotExecuted()
    {
        $tmpfile = 'NotWritten';
        $code = $this->executor->executeCommand('touch ' . $tmpfile);
        $this->assertEquals(0, $code);
        $this->assertFileNotExists($tmpfile);
    }

    public function testReturnsZeroIfCommandWouldFail()
    {
        $code = $this->executor->executeCommand('echo Hey >&2');
        $this->assertEquals(0, $code);
    }
}
