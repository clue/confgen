<?php

namespace Clue\Confgen;

use Twig_Environment;
use RuntimeException;
use KzykHys\FrontMatter\FrontMatter;

class Confgen
{
    private $twig;

    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function processTemplate($template, $data)
    {
        return $this->processDefinition(
            array('templates' => $template),
            $data
        );
    }

    public function processDefinition(array $definition, $data)
    {
        $commands = array();

        $templates = glob($definition['templates']);

        foreach ($templates as $template) {
            $document = $this->extractFrontMatter($template);
            $meta = $document->getConfig();

            // create resulting configuration file by processing template
            $contents = $this->processTemplateContents($document->getContent(), array(
                'data' => $data,
                'meta' => $meta
            ));

            // chmod is either unset or convert decimal to octal notation
            $meta['chmod'] = isset($meta['chmod']) ? decoct($meta['chmod']) : null;

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

    private function filePath($path, $relativeTo)
    {
        if (substr($path, 0, 1) === '/') {
            return $path;
        }
        return dirname($relativeTo) . '/' . $path;
    }

    private function fileContents($file)
    {
        $ret = file_get_contents($file);

        if ($ret === false) {
            throw new \RuntimeException('Unable to read file "' . $file . '"');
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
            $ret = chmod($file, $chmod);
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
}
