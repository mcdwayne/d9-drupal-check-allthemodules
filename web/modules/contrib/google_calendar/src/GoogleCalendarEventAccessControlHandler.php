<?php

namespace Drupal\google_calendar;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Google Calendar Event entity.
 *
 * @see \Drupal\google_calendar\Entity\GoogleCalendarEvent.
 */
class GoogleCalendarEventAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\google_calendar\Entity\GoogleCalendarEventInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished google calendar events');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published google calendar events');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit google calendar events');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete google calendar events');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add google calendar events');
  }

}
