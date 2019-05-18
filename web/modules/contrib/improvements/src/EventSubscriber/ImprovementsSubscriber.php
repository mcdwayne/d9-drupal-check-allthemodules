<?php

namespace Drupal\improvements\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber MyEventSubscriber.
 */
class ImprovementsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespose', 10];
    return $events;
  }

  /**
   * KernelEvents::RESPONSE event callback.
   */
  public function onRespose(FilterResponseEvent $event) {
    // Remove toolbar/toolbar library from page attaachments
    $response = $event->getResponse();
    if (method_exists($response, 'getAttachments')) {
      $attachments = $response->getAttachments();
      $attachments['library'] = array_diff($attachments['library'], ['toolbar/toolbar']);
      $response->setAttachments($attachments);
    }
  }

}
