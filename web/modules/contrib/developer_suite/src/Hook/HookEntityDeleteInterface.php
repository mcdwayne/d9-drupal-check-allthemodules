<?php

namespace Drupal\developer_suite\Hook;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface HookEntityDeleteInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookEntityDeleteInterface {

  /**
   * Executes the entity delete hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object for the entity that has been deleted.
   */
  public function execute(EntityInterface $entity);

}
