<?php

namespace Clue\Confgen;

use Twig_Environment;
use JsonSchema\Validator;

class Factory
{
    public function createConfgen()
    {
        $twig = new Twig_Environment();
        $twig->enableStrictVariables();

        $validator = new Validator();

        return new Confgen($twig, $validator);
    }
}
