<?php

namespace Drupal\maestro\Engine\Exception;

/**
 * Exception thrown during general errors in the engine.
 *
 * @see hook_entity_info_alter()
 */
class MaestroGeneralException extends \Exception {

  /**
   * Constructs an MaestroGeneralException.
   *  
   * @param string $condition
   *   The condition in which this exception ocurred.
   */
  public function __construct($condition) {
    $message = sprintf('General Maestro Error: %s', $condition);
    parent::__construct($message);
  }

}
