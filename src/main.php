<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/test.services.php';

use League\Csv\Reader;

/** @var array<string> */
$args = $_SERVER['argv'];

if (count($args) < 2) {
    error_log('Insufficient number of arguments.');
    exit(1);
}

$sourceFile = $args[1];

if ($sourceFile === '-') {
    // Ideally we want stream here, so createFromPath('php://stdin') could work,
    // but the league/csv throws if the stream is not seekable?
    // Current solution is not efficient, but can be updated later.
    $reader = Reader::createFromString(file_get_contents('php://stdin'));
} else {
    $sourceFile = realpath($sourceFile);
    if ($sourceFile === false) {
        error_log("File \"{$args[1]}\" does not exists or it is not readable file.");
        exit(1);
    }
    $reader = Reader::createFromPath($sourceFile, 'r');
}

$reader->setEscape('');

$weekIndex = [];

foreach ($reader->getRecords($services['record.header']) as $record) {
    $fee = $services['commission']->compute($record);

    echo $fee->getValue() . PHP_EOL;
}
