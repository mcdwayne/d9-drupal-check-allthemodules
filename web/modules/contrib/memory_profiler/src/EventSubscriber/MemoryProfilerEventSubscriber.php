<?php

namespace Drupal\memory_profiler\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Subscribes to the kernel request event to register a shutdown function.
 */
class MemoryProfilerEventSubscriber implements EventSubscriberInterface {

  /**
   * Register memory_profiler_shutdown function.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onRequest(GetResponseEvent $event) {
    drupal_register_shutdown_function('memory_profiler_shutdown');
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    // Set a low value to start as early as possible.
    $events[KernelEvents::REQUEST][] = array('onRequest', -100);

    return $events;
  }

}
