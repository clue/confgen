<?php

use Clue\Confgen\Factory;
use Clue\Confgen\Confgen;

class ConfgenTest extends TestCase
{
    private $confgen;
    private $data;

    public function setUp()
    {
        $this->factory = new Factory();
        $this->confgen = $this->factory->createConfgen();
    }

    public function test01SimpleConfigGenerate()
    {
        chdir(__DIR__ . '/fixtures/01-simple-config');

        $this->confgen->processTemplate('template.twig', $this->loadJson('data.json'));

        // output file successfully generated
        $this->assertFileEquals('output.expected', 'output');
        unlink('output');

        // reload command successfully executed
        $this->assertFileExists('reloaded');
        unlink('reloaded');
    }

    /**
     * @expectedException RuntimeException
     */
    public function test02InvalidTarget()
    {
        chdir(__DIR__ . '/fixtures/02-invalid-target');

        $this->confgen->processTemplate('template', array());
    }

    /**
     * @expectedException Twig_Error_Syntax
     */
    public function test03InvalidTemplate()
    {
        chdir(__DIR__ . '/fixtures/03-invalid-template');

        $this->confgen->processTemplate('template', array());
    }

    public function test04NoTarget()
    {
        chdir(__DIR__ . '/fixtures/04-no-target');

        $this->confgen->processTemplate('example.conf.twig', array());

        // reload command successfully executed
        $this->assertFileExists('example.conf');
        unlink('example.conf');
    }

    public function test05Empty()
    {
        chdir(__DIR__ . '/fixtures/05-empty');

        $this->confgen->processTemplate('template', array());

        $this->assertFileNotExists('empty');
    }

    public function test06Simple()
    {
        chdir(__DIR__ . '/fixtures/06-simple');

        $this->confgen->processTemplate('example.conf.twig', $this->loadJson('data.json'));

        // output file successfully generated
        $this->assertFileEquals('example.conf', 'example.conf.expected');
        unlink('example.conf');
    }

    private function loadJson($path)
    {
        return json_decode(file_get_contents($path));
    }
}
