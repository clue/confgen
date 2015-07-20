<?php

use Clue\Confgen\Factory;

class FactoryTest extends TestCase
{
    public function testOne()
    {
        $factory = new Factory();
        $confgen = $factory->createConfgen();

        $this->assertInstanceOf('Clue\Confgen\Confgen', $confgen);
    }
}
