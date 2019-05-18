<?php

namespace Drupal\events_logging;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface LoggerInterface.
 */
interface LoggerInterface {

  /**
   * @param array $data
   */
  public function log($data);

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function checkIfEntityIsEnabled(EntityInterface $entity);

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   */
  public function createLogEntity(EntityInterface $entity, $type);

}
