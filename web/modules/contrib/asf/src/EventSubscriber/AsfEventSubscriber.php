<?php

/**
 * @file
 * Contains \Drupal\asf\EventSubscriber\AsfEventSubscriber.
 */

namespace Drupal\asf\EventSubscriber;

use Drupal\asf\Event\AsfNodeEvent;
use Drupal\asf\Event\AsfEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriptions for events dispatched by ASF.
 */
class AsfEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[AsfEvents::NODE_PUBLISH][] = ['nodePublish'];
    $events[AsfEvents::NODE_UNPUBLISH][] = ['nodeUnpublish'];
    return $events;
  }

  /**
   * Act on node unpublish event.
   *
   * @param \Drupal\asf\Event\AsfNodeEvent $event
   *   The event to act on.
   */
  public function nodeUnpublish(AsfNodeEvent $event) {
    $node = $event->getNode();

    if ($node->isPublished()) {
      try {
        $node->setPublished(0);
        $node->save();
        $event->setNode($node);

        \Drupal::logger('asf')
          ->notice('Node unpublished: <pre>' . print_r($node->id(), TRUE) . '</pre>');
      } catch (Exception $e) {
        \Drupal::logger('asf')
          ->notice('Node could not be unpublished: <pre>' . print_r($e, TRUE) . '</pre>');
      }
    }
  }

  /**
   * Act on node publish event.
   *
   * @param \Drupal\asf\Event\AsfNodeEvent $event
   *   The event to act on.
   */
  public function nodePublish(AsfNodeEvent $event) {
    $node = $event->getNode();

    if (!$node->isPublished()) {
      try {
        $node->setPublished(1);
        $node->save();
        $event->setNode($node);

        \Drupal::logger('asf')
          ->notice('Node published: <pre>' . print_r($node->id(), TRUE) . '</pre>');
      } catch (Exception $e) {
        \Drupal::logger('asf')
          ->notice('Node could not be published: <pre>' . print_r($e, TRUE) . '</pre>');
      }
    }
  }

}
