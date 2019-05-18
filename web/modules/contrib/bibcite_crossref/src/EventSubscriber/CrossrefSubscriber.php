<?php

namespace Drupal\bibcite_crossref\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CrossrefSubscriber.
 *
 * @package Drupal\bibcite_crossref\EventSubscriber
 */
class CrossrefSubscriber implements EventSubscriberInterface {

  /**
   * Register content type formats on the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $event->getRequest()->setFormat('crossref', ['application/x-crossref-refer']);
  }

  /**
   * Implements \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents().
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    return $events;
  }

}
