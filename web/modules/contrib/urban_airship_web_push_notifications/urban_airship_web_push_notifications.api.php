<?php

use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_urban_airship_web_push_notifications_alter().
 */
function hook_urban_airship_web_push_notifications_alter(&$notification, EntityInterface $entity) {
  // Alter notification message
  // @see Drupal\urban_airship_web_push_notifications\Helper\Notification for variables that can be altered.
}
