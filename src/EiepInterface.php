<?php

namespace Eiep;

use League\Csv\Writer;

/**
 * Interface EiepInterface
 *
 * @package Eiep
 */
interface EiepInterface
{
    function validateHeader(array $header): bool;
    static function validateFilename(string $fileName): bool;
    function streamFromFile(string $fileName, callable $callback): void;
    function createWriter(string $fileName): Writer;
    function writeRecords(string $fileName, array $records): void;
    function getFileName(): string;
}