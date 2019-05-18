<?php

/**
 * @file
 * Setup environment.
 */

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

try {
  // Require your runner files here, runner.php is an example that uses test components (do not use in production)
  // require_once "runner.php";.
}
catch (\Exception $exception) {
  echo "Generic test run failed:\n";
  echo $exception;
  exit(1);
}

echo "Generic test run passed\n";
