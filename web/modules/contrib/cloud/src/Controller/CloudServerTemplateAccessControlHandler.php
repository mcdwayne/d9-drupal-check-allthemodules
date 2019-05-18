<?php

namespace Drupal\cloud\Controller;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Cloud Server Template entity.
 *
 * @see \Drupal\cloud\Entity\CloudServerTemplate.
 */
class CloudServerTemplateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // First check for cloud_context access.
    if (!AccessResult::allowedIfHasPermission($account, 'view ' . $entity->getCloudContext())
      ->isAllowed()
    ) {
      return AccessResult::neutral();
    }

    // Determine if the user is the entity owner id.
    $is_entity_owner = $account->id() == $entity->getOwner()->id();

    /** @var \Drupal\cloud\Entity\CloudServerTemplateInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          if ($account->hasPermission('view any unpublished cloud server template entities')) {
            return AccessResult::allowed();
          }
          return AccessResult::allowedIf($account->hasPermission('view own unpublished cloud server template entities') && $is_entity_owner);
        }

        if ($account->hasPermission('view any published cloud server template entities')) {
          return AccessResult::allowed();
        }
        return AccessResult::allowedIf($account->hasPermission('view own published cloud server template entities') && $is_entity_owner);

      case 'update':
        if ($account->hasPermission('edit any cloud server template entities')) {
          return AccessResult::allowed();
        }
        return AccessResult::allowedIf($account->hasPermission('edit own cloud server template entities') && $is_entity_owner);

      case 'delete':
        if ($account->hasPermission('delete any cloud server template entities')) {
          return AccessResult::allowed();
        }
        return AccessResult::allowedIf($account->hasPermission('delete own cloud server template entities') && $is_entity_owner);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add cloud server template entities');
  }

}
