<?php

namespace Tests;

use RuntimeException;

trait FileTrait
{
    private static function readFile(string $filePath): string
    {
        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException("Could not read file: $filePath");
        }
        return $data;
    }
}
