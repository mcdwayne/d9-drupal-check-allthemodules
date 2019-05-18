<?php
/**
 * @file
 * Implements \Drupal\pathinfo\EventSubscriber\PathinfoRequestSubscriber.
 */

namespace Drupal\pathinfo\EventSubscriber;


use Symfony\Component\HttpKernel\KernelEvents;
//use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PathinfoRequestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onRequest');
    //$events[KernelEvents::RESPONSE][] = array('onResponse');
    return $events;
  }

  public function onResponse(FilterResponseEvent $event) {
  }

  public function onRequest(GetRequestEvent $event) {
    pathinfo_attach();
  }
}
