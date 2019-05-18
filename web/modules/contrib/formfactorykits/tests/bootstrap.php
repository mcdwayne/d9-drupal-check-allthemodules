<?php

/**
 * @file
 * Searches for the core bootstrap file.
 */

$dir = __DIR__;

// Match against previous dir for Windows.
$previous_dir = '';

while ($dir = dirname($dir)) {
  // We've reached the root.
  if ($dir === $previous_dir) {
    break;
  }

  $previous_dir = $dir;

  if (is_file($dir . '/core/tests/bootstrap.php')) {
    // Tell PHPUnit where composer autoloader is.
    if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
      define('PHPUNIT_COMPOSER_INSTALL', $dir . '/autoload.php');
    }

    // Require test-related bootstrap.
    require_once $dir . '/core/tests/bootstrap.php';
    return;
  }
}

throw new RuntimeException('Unable to load core bootstrap.php.');
