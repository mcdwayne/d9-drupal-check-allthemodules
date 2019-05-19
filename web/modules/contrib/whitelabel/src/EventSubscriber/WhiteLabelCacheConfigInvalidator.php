<?php

namespace Drupal\whitelabel\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber invalidating cache tags when the white label settings are saved.
 */
class WhiteLabelCacheConfigInvalidator implements EventSubscriberInterface {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a WhiteLabelCacheConfigInvalidator object.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * Invalidate cache tags when a white label config object changes.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The Event to process.
   */
  public function onChange(ConfigCrudEvent $event) {
    // Check if white label settings object has been changed.
    if ($event->getConfig()->getName() === 'whitelabel.settings') {
      // Invalidate all cached white label data to make sure all cached site
      // names etc. are dropped from any render cache because with the new
      // settings they might not be allowed anymore.
      Cache::invalidateTags(['whitelabel_view']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onChange'];
    return $events;
  }

}
