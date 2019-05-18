<?php

namespace Drupal\entity_delete_op\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Base access checker for performing entity_delete_op operations.
 */
class AccessCheckBase implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new instance of AccessCheckBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Performs access checks based on an operation and entity type.
   *
   * @param string $op
   *   The operation.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access on.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Returns the access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkAccess($op, $entity_type_id, $entity_id, AccountInterface $account) {
    if (empty($op)) {
      return AccessResult::forbidden();
    }

    if ($account->hasPermission('administer entity_delete_op')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($account->hasPermission("entity_delete_op $op any $entity_type_id entities")) {
      return AccessResult::allowed()
        ->cachePerPermissions();
    }

    $entity = $this->entityTypeManager->getStorage($entity_type_id)
      ->load($entity_id);
    if ($entity instanceof EntityOwnerInterface) {
      $access = AccessResult::allowedIf($entity->getOwnerId() == $account->id())
        ->andIf(AccessResult::allowedIf($account->hasPermission("entity_delete_op $op own $entity_type_id entities")))
        ->cachePerUser();
      return $access;
    }

    return AccessResult::forbidden();
  }

}