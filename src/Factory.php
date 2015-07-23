<?php

namespace Clue\Confgen;

use Twig_Environment;
use JsonSchema\Validator;

class Factory
{
    private $twig;
    private $validator;

    /**
     * instantiate new Factory, used to create a `Confgen` instance
     *
     * Optionally, you can explicitly pass an instance of `Twig_Environment` to
     * this constructor. If nothing is passed, it will initialize sane defaults.
     * You may want to pass an instance if you want to use of the following:
     * - custom twig extensions
     * - custom twig functions
     * - custom twig filters
     *
     * Please note that the given `Twig_Environment` instance is mutable.
     * We will automatically assign a new loader and forcefully enable strict
     * variables, no matter what was previously set.
     *
     * Optionally, you can explicitly pass an instance of `JsonSchema\Validator` to
     * this constructor. If nothing is passed, it will initialize sane defaults.
     *
     * @param Twig_Environment|null $twig      (optional) Twig_Environment to use
     * @param Validator|null        $validator (optional) JsonSchema\Validator to use
     */
    public function __construct(Twig_Environment $twig = null, Validator $validator = null)
    {
        if ($twig === null) {
            $twig = new Twig_Environment();
        }
        if ($validator === null) {
            $validator = new Validator();
        }

        $twig->enableStrictVariables();

        $this->twig = $twig;
        $this->validator = $validator;
    }

    public function createConfgen()
    {
        return new Confgen($this->twig, $this->validator);
    }
}
