<?php

use Clue\Confgen\Io\FileSystemLayer;

class FileSystemLayerTest extends TestCase
{
    private $fs;

    public function setUp()
    {
        $this->fs = new FileSystemLayer();
    }

    public function testFuntionalReplaceEmpty()
    {
        chdir(sys_get_temp_dir());
        $temp = tempnam(getcwd(), 'test');

        $this->assertFalse($this->fs->fileContains($temp, 'test'));

        $this->fs->fileReplace($temp, 'test', null);
        $this->assertTrue($this->fs->fileContains($temp, 'test'));

        $this->fs->fileReplace($temp, '', null);
        $this->assertTrue($this->fs->fileContains($temp, ''));
    }

    /**
     * @expectedException Clue\Confgen\Io\FileSystemException
     */
    public function testUnlinkMissing()
    {
        $this->fs->unlink('does-not-exist');
    }

    /**
     * @expectedException Clue\Confgen\Io\FileSystemException
     */
    public function testChmodMissing()
    {
        $this->fs->chmod('does-not-exist', 0777);
    }

    /**
     * @expectedException Clue\Confgen\Io\FileSystemException
     */
    public function testRenameMissing()
    {
        $this->fs->rename('does-not-exist', 'target');
    }
}
