<?php

namespace Drupal\patreon_entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Patreon entity entity.
 *
 * @see \Drupal\patreon_entity\Entity\PatreonEntity.
 */
class PatreonEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $uid = $entity->getOwner();
    $type_id = $entity->getType();

    /** @var \Drupal\patreon_entity\Entity\PatreonEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          $permissions = [
            'view unpublished patreon entity entities',
            'view unpublished ' . $type_id . ' content',
          ];
        }
        else {
          $permissions = [
            'view published patreon entity entities',
            'view published ' . $type_id . ' content',
          ];
        }

        return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');

      case 'update':
        $permissions = [
          'edit patreon entity entities',
          'edit any ' . $type_id . ' content',
        ];
        if ($account->id() == $uid) {
          $permissions[] = 'edit own ' . $type_id . ' content';
        }
        return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');

      case 'delete':
        $permissions = [
          'delete patreon entity entities',
          'delete any ' . $type_id . ' content',
        ];
        if ($account->id() == $uid) {
          $permissions[] = 'edit own ' . $type_id . ' content';
        }
        return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permissions = [
      'add patreon entity entities',
      'create ' . $entity_bundle . ' content',
    ];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
