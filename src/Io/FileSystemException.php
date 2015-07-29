<?php

namespace Clue\Confgen\Io;

use RuntimeException;

class FileSystemException extends RuntimeException
{
    public function __construct($message = null, $code = null, $previous = null)
    {
        if ($message === null) {
            // no message given => try to get last (suppressed) PHP error message
            $error = error_get_last();
            if (isset($error['message'])) {
                $message = $error['message'];
            }
        }
        parent::__construct($message, $code, $previous);
    }
}
