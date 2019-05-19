<?php

namespace Drupal\xframe_allow_webvisor\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribing an event.
 */
class XframeSubscriber implements EventSubscriberInterface {

  /**
   * Executes actions on the respose event.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Filter Response Event object.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $sheme = $event->getRequest()->getScheme();
    $response->headers->set('X-Frame-Options', "Allow-From: {$sheme}://webvisor.com");
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onKernelResponse'];
    return $events;
  }

}
