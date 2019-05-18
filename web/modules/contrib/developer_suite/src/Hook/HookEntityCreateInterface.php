<?php

namespace Drupal\developer_suite\Hook;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface HookEntityCreateInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookEntityCreateInterface {

  /**
   * Executes the entity create hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function execute(EntityInterface $entity);

}
