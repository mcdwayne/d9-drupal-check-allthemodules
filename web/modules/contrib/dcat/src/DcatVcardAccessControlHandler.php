<?php

namespace Drupal\dcat;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the vCard entity.
 *
 * @see \Drupal\dcat\Entity\DcatVcard.
 */
class DcatVcardAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dcat\Entity\DcatVcardInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished vcard entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published vcard entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit vcard entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete vcard entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add vcard entities');
  }

}
