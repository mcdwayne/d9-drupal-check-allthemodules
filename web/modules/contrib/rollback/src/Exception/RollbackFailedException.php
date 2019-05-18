<?php

namespace Drupal\rollback\Exception;

/**
 * Class RollbackFailedException.
 */
class RollbackFailedException extends \Exception {

  /**
   * Constructs an RollbackFailedException.
   *
   * @param Drupal\rollback\RollableUpdate $class
   *   The name of the class.
   */
  public function __construct(RollableUpdate $class) {
    $message = sprintf('%s failed to rollback, validation returned TRUE, expected FALSE for rollback.', get_class($class));
    parent::__construct($message);
  }

}
