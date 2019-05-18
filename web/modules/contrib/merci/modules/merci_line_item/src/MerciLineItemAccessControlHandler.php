<?php

namespace Drupal\merci_line_item;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Merci Line Item entity.
 *
 * @see \Drupal\merci_line_item\Entity\MerciLineItem.
 */
class MerciLineItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\merci_line_item\Entity\MerciLineItemInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished merci line item entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published merci line item entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit merci line item entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete merci line item entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add merci line item entities');
  }

}
