<?php

namespace Drupal\entity_delete_op\Access;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access callback for performing delete operation.
 */
class RestoreAccessCheck extends AccessCheckBase {

  /**
   * Checks access for restore operation.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param int $entity_id
   *   The entity id.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Returns the access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access($entity_type_id, $entity_id, AccountInterface $account) {
    return $this->checkAccess('restore', $entity_type_id, $entity_id, $account);
  }

}
