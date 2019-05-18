<?php

namespace Drupal\brightcove\EventSubscriber;

use Brightcove\API\Client;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to Drupal initialization event.
 */
class BrightcoveInitSubscriber implements EventSubscriberInterface {

  /**
   * Initialize Brightcove client proxy.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   GET response event.
   */
  public function initializeBrightcoveClient(GetResponseEvent $event) {
    Client::$consumer = 'Drupal/' . \Drupal::VERSION . ' Brightcove/' . (system_get_info('module', 'brightcove')['version'] ?: 'dev');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['initializeBrightcoveClient'];
    return $events;
  }

}
