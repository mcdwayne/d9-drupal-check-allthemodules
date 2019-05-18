<?php

namespace Drupal\rollback\Exception;

/**
 * Class UnknownType.
 */
class UnknownType extends \Exception {

  /**
   * Constructs an SchemaNullException.
   *
   * @param string $class
   *   The name of the class.
   */
  public function __construct($class) {
    $message = sprintf('Unable to determine if %s is a class or a service - has the cache been cleared?', $class);
    parent::__construct($message);
  }

}
