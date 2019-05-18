<?php

namespace Drupal\acquia_contenthub_publisher\EventSubscriber\EnqueueEligibility;

use Drupal\acquia_contenthub_publisher\ContentHubPublisherEvents;
use Drupal\acquia_contenthub_publisher\Event\ContentHubEntityEligibilityEvent;
use Drupal\file\FileInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to entity eligibility to prevent enqueueing temporary files.
 */
class FileIsTemporary implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ContentHubPublisherEvents::ENQUEUE_CANDIDATE_ENTITY][] = ['onEnqueueCandidateEntity', 50];
    return $events;
  }

  /**
   * Prevent temporary files from enqueueing.
   *
   * @param \Drupal\acquia_contenthub_publisher\Event\ContentHubEntityEligibilityEvent $event
   *   The event to determine entity eligibility.
   *
   * @throws \Exception
   */
  public function onEnqueueCandidateEntity(ContentHubEntityEligibilityEvent $event) {
    // If this is a file with status = 0 (TEMPORARY FILE) do not export it.
    // This is a check to avoid exporting temporary files.
    $entity = $event->getEntity();
    if ($entity instanceof FileInterface && $entity->isTemporary()) {
      $event->setEligibility(FALSE);
      $event->stopPropagation();
    }
  }

}
