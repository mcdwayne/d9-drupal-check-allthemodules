<?php

namespace Drupal\keycdn\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class KeycdnCacheTagHeaderGenerator.
 *
 * @package Drupal\keycdn
 */
class KeycdnCacheTagHeaderGenerator implements EventSubscriberInterface {

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * Generates a 'Cache-Tag' header in the format expected by KeyCDN.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();
    if (method_exists($response, 'getCacheableMetadata')) {
      $cache_metadata = $response->getCacheableMetadata();
      $cache_tags = $cache_metadata->getCacheTags();
      // If there are no X-Drupal-Cache-Tags headers, do nothing.
      if (!empty($cache_tags)) {
        $hashes = static::cacheTagsToHashes($cache_tags);
        $response->headers->set('Cache-Tag', implode(' ', $hashes));
        $response->headers->set('Content-Length', strlen($response->getContent()));
      }
    }
  }

  /**
   * Maps cache tags to hashes.
   *
   * Advantages:
   *  - Remove invalid chars like -
   *  - Reduces header size as KeyCDN limits at 8k.
   *
   * To see the plain text headers, enable Drupal's built in Header. See
   * https://www.drupal.org/node/2592471.
   *
   * @param string[] $cache_tags
   *   The cache tags in the header.
   *
   * @return string[]
   *   The hashes to use instead in the header.
   */
  public static function cacheTagsToHashes(array $cache_tags) {
    $hashes = [];
    foreach ($cache_tags as $cache_tag) {
      $hashes[] = substr(md5($cache_tag), 0, 3);
    }
    return $hashes;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}
