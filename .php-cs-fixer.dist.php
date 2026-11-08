<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->ignoreVCSIgnored(true);

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PER-CS3.0' => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(false);