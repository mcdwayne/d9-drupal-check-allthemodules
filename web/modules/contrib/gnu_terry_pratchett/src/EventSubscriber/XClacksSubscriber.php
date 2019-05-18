<?php

namespace Drupal\gnu_terry_pratchett\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class XClacksSubscriber implements EventSubscriberInterface {


  /**
   * Sets the X-Clacks-Overhead header on successful responses.
   *
   * @param FilterResponseEvent $event
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();

    // Set the X-Clacks-Overhead HTTP header to keep the legacy of Terry
    // Pratchett alive forever.
    $response->headers->set('X-Clacks-Overhead', 'GNU Terry Pratchett', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onRespond');
    return $events;
  }

}
