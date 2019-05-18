<?php

namespace Drupal\developer_suite\Hook;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface HookEntityPreDeleteInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookEntityPreDeleteInterface {

  /**
   * Executes the entity pre delete hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object for the entity that is about to be deleted.
   */
  public function execute(EntityInterface $entity);

}
