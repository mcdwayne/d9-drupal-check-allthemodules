<?php

namespace Drupal\maestro\Engine\Exception;

/**
 * Exception thrown if the saving of an entity fails.
 *
 * @see hook_entity_info_alter()
 */
class MaestroSaveEntityException extends \Exception {

  /**
   * Constructs an MaestroSaveEntityException.
   *
   * @param string $entity_identifier
   *   The entity identifier.
   *   
   * @param string $condition
   *   The condition in which this exception ocurred.
   */
  public function __construct($entity_identifier, $condition) {
    $message = sprintf('The saving of entity "%s" failed during %s', $entity_identifier, $condition);
    parent::__construct($message);
  }

}
