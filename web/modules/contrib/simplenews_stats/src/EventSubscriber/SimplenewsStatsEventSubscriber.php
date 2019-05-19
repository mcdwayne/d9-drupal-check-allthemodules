<?php

namespace Drupal\simplenews_stats\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Simplenews stats EventSubscriber.
 */
class SimplenewsStatsEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['simplenewsLog', 30];
    return $events;
  }

  /**
   * Catch and log new newsletter hit.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   * @return type
   */
  public function simplenewsLog(GetResponseEvent $event) {
    $value = $event->getRequest()->query->get('sstc');
    if (!$value) {
      return;
    }

    /* @var $SimplenewsStatsEngine \Drupal\simplenews_stats\SimplenewsStatsEngine */
    $simplenewsStatsEngine = \Drupal::service('simplenews_stats.engine');
    $simplenewsStatsEngine->addStatTags($value);
  }

}
