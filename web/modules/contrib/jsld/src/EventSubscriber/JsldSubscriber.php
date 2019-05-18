<?php

namespace Drupal\jsld\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for initialisation of service.
 */
class JsldSubscriber implements EventSubscriberInterface {

  /**
   * Init global service variable.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Response event.
   */
  public function jsldInit(GetResponseEvent $event) {
    \Drupal::service('jsld.global')->init();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['jsldInit'];
    return $events;
  }

}
