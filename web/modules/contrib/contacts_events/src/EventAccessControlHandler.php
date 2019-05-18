<?php

namespace Drupal\contacts_events;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Event entity.
 *
 * @see \Drupal\contacts_events\Entity\Event.
 */
class EventAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\contacts_events\Entity\EventInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished contacts_event entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published contacts_event entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit contacts_event entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete contacts_event entities');

      case 'book':
        if (!$entity->isBookingEnabled()) {
          return AccessResult::forbidden('This event is not configured for bookings.')
            ->addCacheableDependency($entity);
        }

        $permissions = ['can manage bookings for contacts_events'];
        if ($entity->isBookingOpen()) {
          $permissions[] = 'can book for contacts_events';
        }
        return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR')
          ->addCacheableDependency($entity);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add contacts_event entities');
  }

}
