<?php

namespace Drupal\rollback\Exception;

/**
 * Class SchemaNullException.
 */
class SchemaNullException extends \Exception {

  /**
   * Constructs an SchemaNullException.
   *
   * @param string $class
   *   The name of the class.
   */
  public function __construct($class) {
    $message = sprintf('%s does not have a \'schema\' property available', $class);
    parent::__construct($message);
  }

}
