<?php

class BinTest extends TestCase
{
    public function testContainsHelpWhenNoArgumentsGiven()
    {
        chdir(__DIR__ . '/../bin');

        passthru('./confgen 2>&1', $code);

        $this->assertEquals(64, $code);
        $this->expectOutputRegex('/Usage:/');
    }

    public function testContainsHelpWhenHelpArgumentGiven()
    {
        chdir(__DIR__ . '/../bin');

        passthru('./confgen -h 2>&1', $code);

        $this->assertEquals(64, $code);
        $this->expectOutputRegex('/Usage:/');
    }

    public function test04WritesToCurrentWorkingDirectory()
    {
        chdir(__DIR__ . '/fixtures/04-no-target');

        passthru(escapeshellarg(__DIR__ . '/../bin/confgen') . ' -t example.conf.twig 2>&1', $code);

        $this->assertEquals(0, $code);
        $this->expectOutputString('');

        $this->assertFileExists('example.conf');
        unlink('example.conf');
    }

    public function test16StderrContainsStderrFromReloadCommand()
    {
        chdir(__DIR__ . '/fixtures/16-reload-stderr');

        passthru(escapeshellarg(__DIR__ . '/../bin/confgen') . ' -t example.conf.twig 2>&1', $code);

        $this->expectOutputString("done\n");
        unlink("example.conf");
    }

    public function testStderrContainsErrorWhenNoTemplateOrConfigurationArgumentsGiven()
    {
        chdir(__DIR__ . '/../bin');

        $line = exec('./confgen -d composer.json 2>&1', $out, $code);

        $this->assertEquals(64, $code);
        $this->assertContains('Error: Requires', $line);
    }

    public function testStderrContainsErrorWhenTemplateIsNotReadable()
    {
        chdir(__DIR__ . '/../bin');

        $line = exec('./confgen -t /dev/does-not-exist 2>&1', $out, $code);

        $this->assertEquals(66, $code);
        $this->assertContains('Error:', $line);
        $this->assertContains('Unable to read', $line);
    }

    public function test17SkipsCommandExecution()
    {
        chdir(__DIR__ . '/fixtures/17-skip-command-execution');

        passthru(escapeshellarg(__DIR__ . '/../bin/confgen') . ' -t template.twig --no-scripts 2>&1', $code);
        unlink('dummy');

        $this->assertFileNotExists('ShouldNotExist');
    }

    public function test17NoSkipOfReloadCommand()
    {
        chdir(__DIR__ . '/fixtures/17-skip-command-execution');

        // Crosscheck to test 17 without 'skip'
        passthru(escapeshellarg(__DIR__ . '/../bin/confgen') . ' -t template.twig 2>&1', $code);

        $this->assertFileExists('ShouldNotExist');
        unlink('ShouldNotExist');
    }

    public function test18ErrorIfCommandExecutionFailed()
    {
        chdir(__DIR__ . '/fixtures/18-command-execution-failed');

        exec(escapeshellarg(__DIR__ . '/../bin/confgen') . ' -t template.twig 2>&1', $out, $code);

        $this->assertEquals(1, $code);
        unlink('output');
    }
}
