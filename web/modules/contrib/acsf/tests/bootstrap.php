<?php

/**
 * @file
 * Include composer autoload.
 */

// Create aliases if we are running PHPUnit 5.
if (class_exists('PHPUnit_Runner_Version') && version_compare(\PHPUnit_Runner_Version::id(), '6.0.0', '<')) {
  class_alias('PHPUnit_Runner_BaseTestRunner', 'PHPUnit\Runner\BaseTestRunner');
  class_alias('PHPUnit_Framework_Error_Notice', 'PHPUnit\Framework\Error\Notice');
  class_alias('PHPUnit_Framework_Test', 'PHPUnit\Framework\Test');
  class_alias('PHPUnit_Util_Blacklist', 'PHPUnit\Util\Blacklist');
}

require_once __DIR__ . '/../vendor/autoload.php';
