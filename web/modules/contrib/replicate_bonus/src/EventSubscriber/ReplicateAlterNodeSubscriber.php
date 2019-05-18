<?php

namespace Drupal\replicate_bonus\EventSubscriber;

use Drupal\replicate\Events\ReplicateAlterEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ReplicateAlterNodeSubscriber.
 *
 * @package Drupal\replicate_bonus\EventSubscriber
 */
class ReplicateAlterNodeSubscriber implements EventSubscriberInterface {

  /**
   * Changes the cloned entity state to published/unpublished based on the replicate configuration.
   *
   * @param ReplicateAlterEvent $event
   *   The event we're working on.
   */
  public function onNodeClone(ReplicateAlterEvent $event) {
    $config = \Drupal::configFactory()->get('replicate_ui.settings');
    $unpublished_state = $config->get('unpublished_state');
    // We only care about nodes, otherwise all the replicated entities, like
    // paragraphs, will be set to unpublished.
    if($event->getEntity()->getEntityTypeId() == 'node' && $unpublished_state == 1) {
      $event->getEntity()->get('status')->setValue(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ReplicatorEvents::REPLICATE_ALTER][] = 'onNodeClone';
    return $events;
  }
}
