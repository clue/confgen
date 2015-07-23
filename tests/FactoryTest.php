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

    public function testFactoryPassesTwigEnvironmentToConfgen()
    {
        $twig = new Twig_Environment();
        $factory = new Factory($twig);
        $confgen = $factory->createConfgen();

        $property = new \ReflectionProperty($confgen, 'twig');
        $property->setAccessible(true);
        $this->assertSame($twig, $property->getValue($confgen));
    }
}
