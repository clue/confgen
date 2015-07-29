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
    private $schemaMeta;
    private $schemaDefinition;

    public function __construct(Twig_Environment $twig, Validator $validator)
    {
        $this->twig = $twig;
        $this->validator = $validator;

        $this->schemaMeta = $this->fileData(__DIR__ . '/../res/schema-template.json', false);
        $this->schemaDefinition = $this->fileData(__DIR__ . '/../res/schema-confgen.json', false);
    }

    public function processTemplate($templateFile, $dataFile)
    {
        // assert file does actually exist
        $this->fileContents($templateFile);

        return $this->processDefinitionData(
            array('templates' => $templateFile),
            $dataFile === null ? null : $this->fileData($dataFile)
        );
    }

    public function processDefinition($definitionFile, $dataFile)
    {
        return $this->processDefinitionData(
            $this->fileData($definitionFile),
            $dataFile === null ? null : $this->fileData($dataFile)
        );
    }

    private function processDefinitionData(array $definition, $data)
    {
        // validate schema definition
        $this->validate($definition, $this->schemaDefinition);

        $commands = array();

        $templates = glob($definition['templates']);

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
            $target = isset($meta['target']) ? $meta['target'] : basename($template, '.twig');

            // write resulting configuration to target path and apply
            if (!$this->fileContains($target, $contents)) {
                $this->fileReplace($target, $contents, $meta['chmod']);

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
        $contents = $this->fileContents($file);

        return FrontMatter::parse($contents);
    }

    private function fileData($path, $assoc = true)
    {
        $data = json_decode($this->fileContents($path), $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('File "' . $path . '" contains invalid JSON', 65 /* EX_DATAERR */);
        }
        return $data;
    }

    private function fileContents($file)
    {
        $ret = @file_get_contents($file);

        if ($ret === false) {
            throw new \RuntimeException('Unable to read file "' . $file . '"', 66 /* EX_NOINPUT */);
        }

        return $ret;
    }

    private function fileContains($file, $contents)
    {
        // empty contents means file is deleted
        if ($contents === '' && !file_exists($file)) {
            return true;
        }

        return (is_readable($file) && md5_file($file) === md5($contents));
    }

    private function fileReplace($file, $contents, $chmod)
    {
        if ($contents === '') {
            if (file_exists($file)) {
                $ret = unlink($file);
                if ($ret === false) {
                    throw new \RuntimeException('Unable to delete config "' . $file . '"');
                }
            }
            return;
        }

        $temp = $file . '~';

        // first write contents to temporary file
        $ret = @file_put_contents($temp, $contents);
        if ($ret === false) {
            throw new \RuntimeException('Unable to write temp file "' . $temp . '"');
        }

        // apply file mode (chmod) if given
        if ($chmod !== null) {
            $ret = @chmod($temp, $chmod);
            if ($ret === false) {
                // explicitly remove temp file, but ignore its return code
                unlink($temp);

                throw new \RuntimeException('Unable to set file chmod for file "' . $temp . '"');
            }
        }

        $ret = rename($temp, $file);
        if ($ret === false) {
            // explicitly remove temp file, but ignore its return code
            unlink($temp);

            throw new \RuntimeException('Unable to replace config "' . $file . '" with "' . $temp . '"');
        }
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
