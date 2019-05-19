<?php

namespace Drupal\doc_serialization\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for adding DOC content to the request.
 */
class Subscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    return $events;
  }

  /**
   * Register content formats on the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to get the request object to register on.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $event->getRequest()->setFormat('docx', ['application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
  }

}
