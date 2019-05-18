<?php

namespace Drupal\instapage_cms_plugin\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class needed to display custom 404 page in Drupal 8.
 */
class InstapagePluginSubscriber implements EventSubscriberInterface {

  /**
   * Registers a callback.
   *
   * @return array Drupal 8 events array.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onKernelResponse', 5];

    return $events;
  }

  /**
   * Callback function to display 404 page.
   *
   * @param object $event Event object.
   */
  public function onKernelResponse(FilterResponseEvent $event) {

    if (!$event->isMasterRequest()) {
      return;
    }

    $response  = $event->getResponse();
    $statusCode = $response->getStatusCode();

    if ($statusCode == 404) {
      \InstapageCmsPluginConnector::checkPage( '404' );
    }
  }
}
