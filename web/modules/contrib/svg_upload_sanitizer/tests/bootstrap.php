<?php

/**
 * @file
 * Bootstrap file for PHPUnit.
 */

$dirs = [
    'modules',
    'profiles',
    'themes',
];

foreach ($dirs as $dir) {
    $dir = sprintf('%s/../vendor/drupal/%s', __DIR__, $dir);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, TRUE);
    }
}

require sprintf('%s/../vendor/drupal/core/tests/bootstrap.php', __DIR__);
