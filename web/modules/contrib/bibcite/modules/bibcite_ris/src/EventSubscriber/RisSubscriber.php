<?php

namespace Drupal\bibcite_ris\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RisSubscriber.
 *
 * @todo Better class phpdoc.
 */
class RisSubscriber implements EventSubscriberInterface {

  /**
   * Register content type formats on the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $event->getRequest()->setFormat('ris', ['application/x-research-info-systems']);
  }

  /**
   * Implements \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents().
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    return $events;
  }

}
