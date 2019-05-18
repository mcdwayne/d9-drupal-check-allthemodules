<?php

namespace Drupal\rollback\Exception;

/**
 * Class RollableUpdateMissingException.
 */
class RollableUpdateMissingException extends \Exception {

  /**
   * Constructs an RollableUpdateMissingException.
   *
   * @param string $class
   *   The name of the class.
   */
  public function __construct($class) {
    $message = sprintf('%s does not extend the RollableUpdate class', $class);
    parent::__construct($message);
  }

}
