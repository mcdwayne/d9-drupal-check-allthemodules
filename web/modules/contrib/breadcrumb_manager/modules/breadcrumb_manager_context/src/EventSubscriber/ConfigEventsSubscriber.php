<?php

namespace Drupal\breadcrumb_manager_context\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigEventsSubscriber.
 *
 * @package Drupal\breadcrumb_manager_context\EventSubscriber
 */
class ConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'invalidateContextCacheTags',
      ConfigEvents::DELETE => 'invalidateContextCacheTags',
    ];
  }

  /**
   * React to a config object being saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config crud event.
   */
  public function invalidateContextCacheTags(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    // Only act on Context config entities.
    if (!preg_match('/^context\.context\./', $config->getName())) {
      return;
    }

    // Invalidate cache tags only if we've updated a context which uses the
    // breadcrumb reaction.
    $context = $config->getRawData();
    if (isset($context['reactions']['breadcrumb'])) {
      Cache::invalidateTags([
        'breadcrumb_manager',
        'breadcrumb_manager_context',
      ]);
    }
  }

}
