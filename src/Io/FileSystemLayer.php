<?php

namespace Clue\Confgen\Io;

class FileSystemLayer
{
    public function glob($e)
    {
        return glob($e);
    }

    public function basename($b, $e = null)
    {
        return basename($b, $e);
    }

    public function fileData($path, $assoc = true)
    {
        $data = json_decode($this->fileContents($path), $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('File "' . $path . '" contains invalid JSON', 65 /* EX_DATAERR */);
        }
        return $data;
    }

    public function fileContents($file)
    {
        $ret = @file_get_contents($file);

        if ($ret === false) {
            throw new \RuntimeException('Unable to read file "' . $file . '"', 66 /* EX_NOINPUT */);
        }

        return $ret;
    }

    public function fileContains($file, $contents)
    {
        // empty contents means file is deleted
        if ($contents === '' && !file_exists($file)) {
            return true;
        }

        return (is_readable($file) && md5_file($file) === md5($contents));
    }

    public function fileReplace($file, $contents, $chmod)
    {
        if ($contents === '') {
            if (file_exists($file)) {
                try {
                    $this->unlink($file);
                } catch (\RuntimeException $e) {
                    throw new \RuntimeException('Unable to delete config "' . $file . '"', 0, $e);
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

        try {
            if ($chmod !== null) {
                // apply file mode (chmod) to temporary file (if given)
                $this->chmod($temp, $chmod);
            }

            try {
                // overwrite target file with temporary file
                $this->rename($temp, $file);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException('Unable to replace config "'. $file . '" with "' . $temp . '"', 0, $e);
            }
        } catch (\RuntimeException $e) {
            try {
                $this->unlink($temp);
            } catch (\RuntimeException $ignored) {
                // explicitly remove temp file, but ignore its return code
            }

            throw $e;
        }
    }

    public function unlink($file)
    {
        $ret = unlink($file);
        if ($ret === false) {
            throw new \RuntimeException('Unable to delete "' . $file . '"');
        }
    }

    public function chmod($file, $chmod)
    {
        $ret = @chmod($file, $chmod);
        if ($ret === false) {
            throw new \RuntimeException('Unable to set file chmod for file "' . $file . '"');
        }
    }

    public function rename($old, $new)
    {
        $ret = rename($old, $new);
        if ($ret === false) {
            throw new \RuntimeException('Unable to rename "' . $old . '" to "' . $new . '"');
        }
    }
}
