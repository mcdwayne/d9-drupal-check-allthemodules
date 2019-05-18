<?php

namespace Drupal\audit_log\EventSubscriber;

use Drupal\audit_log\AuditLogEventInterface;
use Drupal\Core\Render\Markup;
use Drupal\user\UserInterface;

/**
 * Processes User entity events.
 *
 * @package Drupal\audit_log\EventSubscriber
 */
class User implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function reactTo(AuditLogEventInterface $event) {
    /** @var \Drupal\user\Entity\User $entity */
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() != $this->getEntityType()) {
      return FALSE;
    }
    $event_type = $event->getEventType();
    $args = ['@name' => Markup::create($entity->label())];
    $current_state = $entity->status->value ? 'active' : 'blocked';
    $original_state = NULL;
    if (isset($entity->original) && $entity->original instanceof UserInterface) {
      $original_state = $entity->original->status->value ? 'active' : 'blocked';
    }

    if ($event_type == 'insert') {
      $event
        ->setMessage(t('@name was created.', $args))
        ->setPreviousState(NULL)
        ->setCurrentState($current_state);
      return TRUE;
    }

    if ($event_type == 'update') {
      $event
        ->setMessage(t('@name was updated.', $args))
        ->setPreviousState($original_state)
        ->setCurrentState($current_state);
      return TRUE;
    }

    if ($event_type == 'delete') {
      $event
        ->setMessage(t('@name was deleted.', $args))
        ->setPreviousState($original_state)
        ->setCurrentState(NULL);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'user';
  }

}
