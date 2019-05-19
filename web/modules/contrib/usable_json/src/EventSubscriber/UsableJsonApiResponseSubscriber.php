<?php

namespace Drupal\usable_json\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber to handle api cache tags sets.
 */
class UsableJsonApiResponseSubscriber implements EventSubscriberInterface {

  /**
   * Sets extra headers on successful responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    $config = \Drupal::config('usable_json.api');
    $request = $event->getRequest();
    $response = $event->getResponse();
    if ($request->get('_format') == 'usable_json' && $config->get('send_cache_tags') && $response instanceof CacheableResponseInterface) {
      $response_cacheability = $response->getCacheableMetadata();
      $response->headers->set('X-Cache-Tags', implode(' ', $response_cacheability->getCacheTags()));
    }
  }

  /**
   * Register content type formats on the request object.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $event->getRequest()->setFormat('usable_json', array('application/json'));
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest');
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}
