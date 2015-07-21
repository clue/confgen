<?php

namespace Clue\Confgen;

use Twig_Loader_String;
use Twig_Environment;

class Factory
{
    public function createConfgen()
    {
        // documentation explicitly warns against using this loader because it
        // will be removed with Twig v2.0 eventually.
        // However, it exactly implements our use case because template contents
        // are always held in memory.
        $loader = new Twig_Loader_String();

        $twig = new Twig_Environment($loader);
        $twig->enableStrictVariables();

        return new Confgen($twig);
    }
}
