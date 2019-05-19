<?php

namespace Drupal\visualn_iframe\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

// @see: https://drupal.stackexchange.com/questions/188924/how-to-embed-drupal-content-in-other-sites-remove-x-frame-options-sameorigin/201297
class SetXFrameOptionsSubscriber implements EventSubscriberInterface {

  public function RemoveXFrameOptions(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->remove('X-Frame-Options');
    // @todo: for per-iframe integration check https://www.drupal.org/project/http_response_headers
    //    or add the feature to the given module (maybe store rules in the visualn_iframes_data db table)
    //$response->headers->set('X-Frame-Options', 'ALLOW-FROM https://ALLOWED.SITE/');
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('RemoveXFrameOptions', -100);
    return $events;
  }
}
