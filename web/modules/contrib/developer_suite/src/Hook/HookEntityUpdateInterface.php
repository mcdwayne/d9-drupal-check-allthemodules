<?php

namespace Drupal\developer_suite\Hook;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface HookEntityUpdateInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookEntityUpdateInterface {

  /**
   * Executes the entity update hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function execute(EntityInterface $entity);

}
