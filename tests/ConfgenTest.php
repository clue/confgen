<?php

use Clue\Confgen\Factory;
use Clue\Confgen\Executor\NullExecutor;

class ConfgenTest extends TestCase
{
    private $confgen;
    private $data;

    public function setUp()
    {
        $this->factory = new Factory();
        $this->confgen = $this->factory->createConfgen();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionCode 66
     */
    public function testProcessTemplateMissingFails()
    {
        $this->confgen->processTemplate('/dev/does-not-exist', null);
    }

    public function test01SimpleConfigGenerate()
    {
        chdir(__DIR__ . '/fixtures/01-simple-config');

        $this->confgen->processTemplate('template.twig', 'data.json');

        // output file successfully generated
        $this->assertFileEquals('output.expected', 'output');
        unlink('output');

        // reload command successfully executed
        $this->assertFileExists('reloaded');
        unlink('reloaded');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionCode 66
     */
    public function testProcessTemplateDataMissingFails()
    {
        chdir(__DIR__ . '/fixtures/01-simple-config');

        $this->confgen->processTemplate('template.twig', 'does-not-exist.json');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionCode 65
     */
    public function testProcessTemplateDataNotJsonFails()
    {
        chdir(__DIR__ . '/fixtures/01-simple-config');

        $this->confgen->processTemplate('template.twig', 'template.twig');
    }

    /**
     * @expectedException RuntimeException
     */
    public function test02InvalidTarget()
    {
        chdir(__DIR__ . '/fixtures/02-invalid-target');

        $this->confgen->processTemplate('template', null);
    }

    public function test03InvalidTemplate()
    {
        chdir(__DIR__ . '/fixtures/03-invalid-template');

        try {
            $this->confgen->processTemplate('template', null);
            $this->fail('Should not be reached');
        } catch (Twig_Error_Syntax $e) {
            $this->assertTrue(true);

            $this->assertEquals('template', $e->getTemplateFile());
        }
    }

    public function test04NoTarget()
    {
        chdir(__DIR__ . '/fixtures/04-no-target');

        $this->confgen->processTemplate('example.conf.twig', null);

        // reload command successfully executed
        $this->assertFileExists('example.conf');
        unlink('example.conf');
    }

    public function test05Empty()
    {
        chdir(__DIR__ . '/fixtures/05-empty');

        $this->confgen->processTemplate('template', null);

        $this->assertFileNotExists('empty');
    }

    public function test06Simple()
    {
        chdir(__DIR__ . '/fixtures/06-simple');

        $this->confgen->processTemplate('example.conf.twig', 'data.json');

        // output file successfully generated
        $this->assertFileEquals('example.conf', 'example.conf.expected');
        unlink('example.conf');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionCode 65
     */
    public function test07InvalidMeta()
    {
        chdir(__DIR__ . '/fixtures/07-invalid-meta');

        $this->confgen->processTemplate('template', null);
    }

    public function test08DefinitionSimple()
    {
        chdir(__DIR__ . '/fixtures/08-definition-simple');

        $this->confgen->processDefinition('confgen.json', null);

        // output file successfully written
        $this->assertFileExists('example.conf');
        unlink('example.conf');
    }

    public function test08DefinitionSimpleRelativeGlobPath()
    {
        $path = __DIR__ . '/fixtures/08-definition-simple/';

        $this->confgen->processDefinition($path . 'confgen.json', null);

        // output file successfully written to CURRENT(!) directory
        $this->assertFileExists('example.conf');
        unlink('example.conf');
    }

    public function test09DefinitionEmpty()
    {
        chdir(__DIR__ . '/fixtures/09-definition-empty');

        $this->confgen->processDefinition('confgen.json', null);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionCode 65
     */
    public function test10DefinitionInvalidNoTemplates()
    {
        chdir(__DIR__ . '/fixtures/10-definition-invalid-no-templates');

        $this->confgen->processDefinition('confgen.json', null);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionCode 65
     */
    public function test11DefinitionInvalidNoJson()
    {
        chdir(__DIR__ . '/fixtures/11-definition-invalid-no-json');

        $this->confgen->processDefinition('invalid.json', null);
    }

    /**
     * @expectedException Twig_Error_Syntax
     */
    public function test12CustomFilterFailsWhenNotRegistered()
    {
        chdir(__DIR__ . '/fixtures/12-custom-filter');

        $this->confgen->processTemplate('filter.twig', null);
    }

    public function test12CustomFilterWorksWhenRegistered()
    {
        chdir(__DIR__ . '/fixtures/12-custom-filter');

        $twig = new Twig_Environment();
        $twig->addFilter(new Twig_SimpleFilter('test', function ($value) { return 'yes'; }));

        $this->factory = new Factory($twig);
        $this->confgen = $this->factory->createConfgen();

        $this->confgen->processTemplate('filter.twig', null);

        // output file successfully written
        $this->assertFileExists('filter');
        unlink('filter');
    }

    public function test13Chmod()
    {
        chdir(__DIR__ . '/fixtures/13-chmod');

        $this->confgen->processTemplate('template.twig', null);

        // output file successfully generated with correct permissions
        $this->assertFileEquals('output.expected', 'output');
        $this->assertEquals(0777, fileperms('output') & 0777);
        unlink('output');
    }

    public function test14IgnoreOutputToStdout()
    {
        chdir(__DIR__ . '/fixtures/14-stdout-ignored');

        $this->expectOutputString('');
        $this->confgen->processTemplate('template.twig', 'data.json');
        unlink('output');
    }

    public function test15NoStdoutOutputEvenIfStderr()
    {
        chdir(__DIR__ . '/fixtures/15-stderr-output');

        $this->expectOutputString('');
        $this->confgen->processTemplate('template.twig', 'data.json');
        unlink('output');
    }

    public function test17SkipReloadCommandExecution()
    {
        chdir(__DIR__ . '/fixtures/17-skip-command-execution');

        $factory = new Factory(null, null, null, new NullExecutor());
        $confgen = $factory->createConfgen();

        $confgen->processTemplate('template.twig', 'data.json');
        unlink('dummy');

        // reload command is skipped due to no-scripts flag
        $this->assertFileNotExists('ShouldNotExist');
    }

    public function test18CommandExecutionFailed()
    {
        chdir(__DIR__ . '/fixtures/18-command-execution-failed');

        // Must catch it manually due to fact that unlink is not executed when testing for exception
        try {
            $this->confgen->processTemplate('template.twig', null);
        } catch (RuntimeException $e) {
            $this->assertTrue(true);
            unlink('output');
            return;
        }
        $this->fail('RuntimeException not thrown');
    }
}
