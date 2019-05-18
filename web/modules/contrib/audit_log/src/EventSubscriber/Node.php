<?php

namespace Drupal\audit_log\EventSubscriber;

use Drupal\audit_log\AuditLogEventInterface;
use Drupal\Core\Render\Markup;

/**
 * Processes node entity events.
 *
 * @package Drupal\audit_log\EventSubscriber
 */
class Node implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function reactTo(AuditLogEventInterface $event) {
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() != $this->getEntityType()) {
      return FALSE;
    }
    $event_type = $event->getEventType();
    /** @var \Drupal\node\NodeInterface $entity */
    $current_state = $entity->isPublished() ? 'published' : 'unpublished';
    $previous_state = '';
    if (isset($entity->original)) {
      $previous_state = $entity->original->isPublished() ? 'published' : 'unpublished';
    }
    $args = [
      '@title' => Markup::create($entity->label()),
    ];

    if ($event_type == 'insert') {
      $event
        ->setMessage(t('@title was created.', $args))
        ->setPreviousState(NULL)
        ->setCurrentState($current_state);
      return TRUE;
    }

    if ($event_type == 'update') {
      $event
        ->setMessage(t('@title was updated.', $args))
        ->setPreviousState($previous_state)
        ->setCurrentState($current_state);
      return TRUE;
    }

    if ($event_type == 'delete') {
      $event
        ->setMessage(t('@title was deleted.', $args))
        ->setPreviousState($previous_state)
        ->setCurrentState(NULL);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'node';
  }

}
