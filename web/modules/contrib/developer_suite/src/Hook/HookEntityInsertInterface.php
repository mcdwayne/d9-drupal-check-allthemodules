<?php

namespace Drupal\developer_suite\Hook;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface HookEntityInsertInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookEntityInsertInterface {

  /**
   * Executes the entity insert hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function execute(EntityInterface $entity);

}
