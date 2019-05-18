<?php

namespace Drupal\magicblocks\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MagicBlocksEventSubscriber implements EventSubscriberInterface {

  /**
   * Massage headers of response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }
    $response = $event->getResponse();
    if (!is_null($response->headers->get('magicblocks-overide-x-frame-options'))) {
      $response->headers->remove('magicblocks-overide-x-frame-options');
      $response->headers->remove('x-frame-options');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // We want to come after @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber
    $events[KernelEvents::RESPONSE][] = ['onKernelResponse', -100];
    return $events;
  }

}
