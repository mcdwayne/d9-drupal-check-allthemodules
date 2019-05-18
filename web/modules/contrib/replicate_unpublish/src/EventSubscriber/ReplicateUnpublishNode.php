<?php

namespace Drupal\replicate_unpublish\EventSubscriber;

use Drupal\node\Entity\Node;
use Drupal\replicate\Events\ReplicateAlterEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Makes  the node unpublished after it replicated.
 */
class ReplicateUnpublishNode implements EventSubscriberInterface {

  /**
   * Sets the status of a replicated node to unpublished.
   *
   * @param \Drupal\replicate\Events\ReplicateAlterEvent $event
   *  The event fired by the replicator.
   *  For more details look at replicate_api doc.
   *
   */
  public function setUnpublished(ReplicateAlterEvent $event) {
    $cloned_entity = $event->getEntity();

    if (!$cloned_entity instanceof Node) {
      return;
    }

    $cloned_entity->set('status', Node::NOT_PUBLISHED);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ReplicatorEvents::REPLICATE_ALTER][] = 'setUnpublished';
    return $events;
  }

}