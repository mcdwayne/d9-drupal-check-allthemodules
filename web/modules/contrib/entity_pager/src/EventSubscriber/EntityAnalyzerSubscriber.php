<?php

namespace Drupal\entity_pager\EventSubscriber;

use Drupal\entity_pager\Event\EntityPagerAnalyzeEvent;
use Drupal\entity_pager\Event\EntityPagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A simple event subscriber that provides feedback about the current entity
 * used by the entity pager.
 */
class EntityAnalyzerSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    $events[EntityPagerEvents::ENTITY_PAGER_ANALYZE][] = ['onEntityPagerAnalyze'];

    return $events;
  }

  /**
   * Check if there is a valid entity for the pager.
   *
   * @param \Drupal\entity_pager\Event\EntityPagerAnalyzeEvent $event
   */
  public function onEntityPagerAnalyze(EntityPagerAnalyzeEvent $event) {
    $entity = $event->getEntityPager()->getEntity();

    if (!$entity) {
      $event->log('No Entity on page.');
    }
  }
}
