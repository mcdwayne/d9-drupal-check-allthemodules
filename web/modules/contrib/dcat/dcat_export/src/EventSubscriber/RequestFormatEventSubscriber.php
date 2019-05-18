<?php

namespace Drupal\dcat_export\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for adding additional content types to the request.
 */
class RequestFormatEventSubscriber implements EventSubscriberInterface {

  /**
   * Register content type formats on the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The GetResponseEvent event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    $request->setFormat('ttl', ['text/turtle']);
    $request->setFormat('nt', ['application/n-triples']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    return $events;
  }
}
