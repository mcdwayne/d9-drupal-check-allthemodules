<?php

namespace Drupal\mailing_list;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Access controller for the subscription entity.
 *
 * @see \Drupal\mailing_list\Entity\Subscription.
 */
class SubscriptionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\mailing_list\SubscriptionInterface $entity */

    // Subscriptions administrators have global access.
    if ($account->hasPermission('administer mailing list subscriptions')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Treat view label operation as view.
    if ($operation == 'view label') {
      $operation = 'view';
    }

    $list_id = $entity->getListId();

    // Inactive subscription access.
    if (!$entity->isActive() && !$account->hasPermission("access inactive $list_id mailing list subscriptions")) {
      return AccessResult::forbidden();
    }

    // Subscription owner check.
    $is_owner = $account->id() == $entity->getOwnerId();
    if ($account->isAnonymous() || !$is_owner) {
      // Check for session grants.
      $is_owner = \Drupal::service('mailing_list.manager')->hasSessionAccess($entity);
    }

    // Access allowed if user has unrestricted access or is the owner and can
    // subscribe to such mailing list.
    if ($account->hasPermission("$operation any $list_id mailing list subscriptions")
      || ($is_owner && $account->hasPermission("subscribe to $list_id mailing list"))) {
      return AccessResult::allowed();
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIf($account->hasPermission('administer mailing list subscriptions')
      || $account->hasPermission("subscribe to $entity_bundle mailing list"))->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    if ($operation == 'edit') {
      // Only users with the administer nodes permission can edit administrative
      // fields.
      $administrative_fields = ['uid', 'created', 'status'];
      if (in_array($field_definition->getName(), $administrative_fields, TRUE)) {
        return AccessResult::allowedIfHasPermission($account, 'administer mailing list subscriptions');
      }
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
