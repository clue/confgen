<?php

namespace Clue\Confgen;

use Twig_Environment;
use JsonSchema\Validator;
use Clue\Confgen\Io\FileSystemLayer;
use Clue\Confgen\Executor\ExecutorInterface;
use Clue\Confgen\Executor\StdoutSuppressingSystemExecutor;

class Factory
{
    private $twig;
    private $validator;
    private $fs;
    private $executor;

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
     * Optionally, you can explicitly pass an instance of `Io\FileSystemLayer` to
     * this constructor. If nothing is passed, it will initialize sane defaults.
     *
     * Optionally, you can explicitly pass an instance implementing `Executor\ExecutorInterface` to
     * this constructor. If nothing is passed, it will initialize sane defaults.
     *
     * @param Twig_Environment|null  $twig      (optional) Twig_Environment to use
     * @param Validator|null         $validator (optional) JsonSchema\Validator to use
     * @param FileSystemLayer|null   $fs        (optional) Io\FileSystemLayer to use
     * @param ExecutorInterface|null $executor  (optional) Executor\ExecutorInterface to use
     */
    public function __construct(Twig_Environment $twig = null, Validator $validator = null, FileSystemLayer $fs = null, ExecutorInterface $executor = null)
    {
        if ($twig === null) {
            $twig = new Twig_Environment();
        }
        if ($validator === null) {
            $validator = new Validator();
        }
        if ($fs === null) {
            $fs = new FileSystemLayer();
        }
        if ($executor === null) {
            $executor = new StdoutSuppressingSystemExecutor();
        }

        $twig->enableStrictVariables();

        $this->twig = $twig;
        $this->validator = $validator;
        $this->fs = $fs;
        $this->executor = $executor;
    }

    public function createConfgen()
    {
        return new Confgen($this->twig, $this->validator, $this->fs, $this->executor);
    }
}
