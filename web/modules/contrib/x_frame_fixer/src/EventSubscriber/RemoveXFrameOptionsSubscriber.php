<?php

namespace Drupal\x_frame_fixer\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RemoveXFrameOptionsSubscriber implements EventSubscriberInterface {

  public function RemoveXFrameOptions(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->remove('X-Frame-Options');
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('RemoveXFrameOptions', -10);
    return $events;
  }
}
