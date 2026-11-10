<?php

namespace App\Content;

use RuntimeException;

class InvalidContentException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors  keyed by file name
     */
    public function __construct(public readonly array $errors)
    {
        $lines = [];
        foreach ($errors as $file => $messages) {
            foreach ($messages as $message) {
                $lines[] = "{$file}: {$message}";
            }
        }

        parent::__construct("Invalid content:\n".implode("\n", $lines));
    }
}
