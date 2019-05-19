<?php

namespace Drupal\urban_airship_web_push_notifications\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remove X-Frame-Options from the header on `web-push-secure-bridge.html` page.
 */
class RemoveFrameOptions implements EventSubscriberInterface {

  public function onResponse(FilterResponseEvent $event) {
    $current_uri = \Drupal::request()->getRequestUri();
    if (strstr($current_uri, 'web-push-secure-bridge.html')) {
      $response = $event->getResponse();
      $response->headers->remove('X-Frame-Options');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse', -1024];
    return $events;
  }

}
