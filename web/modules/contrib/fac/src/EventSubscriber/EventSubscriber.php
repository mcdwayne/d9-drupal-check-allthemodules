<?php

namespace Drupal\fac\EventSubscriber;

use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventSubscriber.
 *
 * Subscribes to the alter_excluded_paths event from the
 * stage_file_proxy module to exclude the path for the
 * json files of the fac module.
 *
 * @package Drupal\fac\EventSubscriber
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Register events and callbacks.
   *
   * @return array
   *   The array of registered events with callbacks.
   */
  public static function getSubscribedEvents() {
    $events['stage_file_proxy.alter_excluded_paths'][] = [
      'alterExcludedPaths',
      0,
    ];
    return $events;
  }

  /**
   * The excluded paths event callback.
   *
   * @param object $event
   *   The event.
   */
  public function alterExcludedPaths($event) {
    $excluded_paths = $event->getExcludedPaths();
    $excluded_paths[] = PublicStream::basePath() . '/fac-json';
    $event->setExcludedPaths($excluded_paths);
  }

}
