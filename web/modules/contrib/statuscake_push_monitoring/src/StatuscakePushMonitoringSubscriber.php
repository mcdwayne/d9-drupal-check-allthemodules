<?php
/**
 * @file
 * Contains \Drupal\mymodule\StatuscakePushMonitoringSubscriber.
 */

namespace Drupal\statuscake_push_monitoring;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a StatuscakePushMonitoringSubscriber.
 */
class StatuscakePushMonitoringSubscriber implements EventSubscriberInterface {

  /**
   * Starting timer on every page request.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   *
   * @see Symfony\Component\HttpKernel\KernelEvents
   */
  public function statuscakePushMonitoringLoad(GetResponseEvent $event) {
    // @todo remove this debug code
    $time_start = &drupal_static('statuscake_push_monitoring_start_time');
    $time_start = statuscake_push_monitoring_microtime_float();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('statuscakePushMonitoringLoad');
    return $events;
  }

}
