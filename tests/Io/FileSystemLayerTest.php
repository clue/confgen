<?php

use Clue\Confgen\Io\FileSystemLayer;

class FileSystemLayerTest extends TestCase
{
    private $fs;

    public function setUp()
    {
        $this->fs = new FileSystemLayer();
    }

    public function testFunctionalReplaceEmpty()
    {
        chdir(sys_get_temp_dir());
        $temp = tempnam(getcwd(), 'test');

        $this->assertFalse($this->fs->fileContains($temp, 'test'));

        $this->fs->fileReplace($temp, 'test', null);
        $this->assertTrue($this->fs->fileContains($temp, 'test'));

        $this->fs->fileReplace($temp, '', null);
        $this->assertTrue($this->fs->fileContains($temp, ''));
    }

    public function testContainsMissing()
    {
        $temp = 'does-not-exist';

        $this->assertTrue($this->fs->fileContains($temp, ''));
    }

    public function testReplaceEmpty()
    {
        $temp = 'does-not-exist';

        $this->fs->fileReplace($temp, '', null);
        $this->assertTrue($this->fs->fileContains($temp, ''));
        $this->assertFileNotExists($temp);
    }

    /**
     * @expectedException Clue\Confgen\Io\FileSystemException
     */
    public function testReplaceEmptyNotWritable()
    {
        $temp = '/dev/null';

        $this->fs->fileReplace($temp, '', null);
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
