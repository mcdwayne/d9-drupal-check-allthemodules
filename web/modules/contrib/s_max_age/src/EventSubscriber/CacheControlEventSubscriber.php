<?php
namespace Drupal\s_max_age\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Subscriber for adding s-maxage.
 */
class CacheControlEventSubscriber implements EventSubscriberInterface {

  /**
   * Ensure s-maxage is set.
   */
  public function setHeaderCacheControl(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $cache = $response->headers->get('Cache-Control');

    // Only set an s-maxage if a max-age is present.
    if (!preg_match('/max-age=([0-9]+)/', $cache)) {
      return;
    }

    // TODO, make max-age configurable.
    $cache = str_replace('max-age=', 'max-age=0, s-maxage=', $cache);

    // Set the header.
    $response->headers->set('Cache-Control', $cache, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Response: set header content for security policy.
    $events[KernelEvents::RESPONSE][] = ['setHeaderCacheControl', -10];
    return $events;
  }

}
