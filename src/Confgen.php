<?php

namespace Clue\Confgen;

use Twig_Environment;
use Twig_Loader_Array;
use RuntimeException;
use KzykHys\FrontMatter\FrontMatter;
use JsonSchema\Validator;

class Confgen
{
    private $twig;
    private $validator;
    private $fs;

    private $schemaMeta;
    private $schemaDefinition;

    public function __construct(Twig_Environment $twig, Validator $validator, FileSystemLayer $fs = null)
    {
        if ($fs === null) {
            $fs = new FileSystemLayer();
        }

        $this->twig = $twig;
        $this->validator = $validator;
        $this->fs = $fs;

        $this->schemaMeta = $this->fs->fileData(__DIR__ . '/../res/schema-template.json', false);
        $this->schemaDefinition = $this->fs->fileData(__DIR__ . '/../res/schema-confgen.json', false);
    }

    public function processTemplate($templateFile, $dataFile)
    {
        return $this->processTemplatesData(
            array($templateFile),
            $dataFile === null ? null : $this->fs->fileData($dataFile)
        );
    }

    public function processDefinition($definitionFile, $dataFile)
    {
        $definition = $this->fs->fileData($definitionFile);

        // validate schema definition
        $this->validate($definition, $this->schemaDefinition);

        $templates = $this->fs->glob($definition['templates']);

        return $this->processTemplatesData(
            $templates,
            $dataFile === null ? null : $this->fs->fileData($dataFile)
        );
    }

    private function processTemplatesData(array $templates, $data)
    {
        $commands = array();

        foreach ($templates as $template) {
            $document = $this->extractFrontMatter($template);
            $meta = $document->getConfig();

            // validate meta data variables
            $this->validate($meta, $this->schemaMeta);

            // use a new dummy loader whose sole responsibilty is mapping template name to contents
            // this allows us to reference the template by name (and receive according error messages)
            $this->twig->setLoader(new Twig_Loader_Array(array($template => $document->getContent())));

            // create resulting configuration file by processing template
            $contents = $this->processTemplateContents($template, array(
                'data' => $data,
                'meta' => $meta
            ));

            // chmod is either unset or convert decimal to octal notation
            $meta['chmod'] = isset($meta['chmod']) ? octdec($meta['chmod']) : null;

            // target file name can either be given or defaults to template name without ".twig" extension
            $target = isset($meta['target']) ? $meta['target'] : $this->fs->basename($template, '.twig');

            // write resulting configuration to target path and apply
            if (!$this->fs->fileContains($target, $contents)) {
                $this->fs->fileReplace($target, $contents, $meta['chmod']);

                // template has a command definition to reload this file
                if (isset($meta['reload'])) {
                    $commands []= $meta['reload'];
                }
            }
        }

        // let's reload all the files after (successfully) writing all of them
        foreach ($commands as $command) {
            $this->execute($command);
        }
    }

    private function processTemplateContents($template, $data)
    {
        return $this->twig->render($template, $data);
    }

    private function extractFrontMatter($file)
    {
        $contents = $this->fs->fileContents($file);

        return FrontMatter::parse($contents);
    }

    private function execute($command)
    {
        exec($command, $ret, $code);
        if ($code !== 0) {
            throw new \RuntimeException('Unable to execute "' . $command . '"');
        }
    }

    private function validate($data, $schema)
    {
        $this->validator->reset();
        $this->validator->check((object)$data, $schema);

        if (!$this->validator->isValid()) {
            $message = 'Unable to validate template meta data';
            foreach ($this->validator->getErrors() as $error) {
                $message .= PHP_EOL . $error['message'];
            }
            throw new \RuntimeException($message, 65 /*EX_DATAERR */);
        }
    }
}
