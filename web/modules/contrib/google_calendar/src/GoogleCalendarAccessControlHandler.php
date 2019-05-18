<?php

namespace Drupal\google_calendar;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Google Calendar entity.
 *
 * @see \Drupal\google_calendar\Entity\GoogleCalendar.
 */
class GoogleCalendarAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\google_calendar\Entity\GoogleCalendarInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished google calendars');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published google calendars');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit google calendars');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete google calendars');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add google calendars');
  }

}
