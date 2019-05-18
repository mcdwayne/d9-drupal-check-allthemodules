<?php

namespace Drupal\drupal_coverage_core\Exception;

/**
 * An exception thrown for non-exsting modules.
 *
 * Examples could be when a core module does not exist or when a contrib module
 * does not exist.
 */
class ModuleDoesNotExistException extends \Exception {}
