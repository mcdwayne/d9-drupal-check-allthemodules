<?php

namespace Drupal\acquia_contenthub_publisher\EventSubscriber\EnqueueEligibility;

use Drupal\acquia_contenthub_publisher\ContentHubPublisherEvents;
use Drupal\acquia_contenthub_publisher\Event\ContentHubEntityEligibilityEvent;
use Drupal\Core\Entity\RevisionableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to entity eligibility to prevent enqueuing unpublished revisions.
 */
class RevisionIsPublished implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ContentHubPublisherEvents::ENQUEUE_CANDIDATE_ENTITY][] = ['onEnqueueCandidateEntity', 100];
    return $events;
  }

  /**
   * Prevent entity revisions that are not published from being enqueued.
   *
   * @param \Drupal\acquia_contenthub_publisher\Event\ContentHubEntityEligibilityEvent $event
   *   The event to determine entity eligibility.
   *
   * @throws \Exception
   */
  public function onEnqueueCandidateEntity(ContentHubEntityEligibilityEvent $event) {
    $entity = $event->getEntity();
    $status = $entity->getEntityType()->hasKey("status") ? $entity->getEntityType()->getKey("status") : NULL;
    $revision_col = $entity->getEntityType()->hasKey("revision") ? $entity->getEntityType()->getKey("revision") : NULL;
    if ($status && $revision_col && $entity instanceof RevisionableInterface) {
      $definition = $entity->getFieldDefinition($status);
      $property = $definition->getFieldStorageDefinition()->getMainPropertyName();
      $value = $entity->get($status)->$property;
      if (!$value) {
        $table = $entity->getEntityType()->getBaseTable();
        $id_col = $entity->getEntityType()->getKey("id");
        $query = \Drupal::database()->select($table)
          ->fields($table, [$revision_col]);
        $query->condition("$table.$id_col", $entity->id());
        $revision_id = $query->execute()->fetchField();
        if ($revision_id != $entity->getRevisionId()) {
          $event->setEligibility(FALSE);
          $event->stopPropagation();
        }
      }
    }
  }

}
