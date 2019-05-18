<?php

namespace Drupal\developer_suite\Hook;

/**
 * Interface HookEntityLoadInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookEntityLoadInterface {

  /**
   * Executes the entity load hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities keyed by entity ID.
   * @param string $entity_type_id
   *   The type of entities being loaded (i.e. node, user, comment).
   */
  public function execute(array $entities, $entity_type_id);

}
