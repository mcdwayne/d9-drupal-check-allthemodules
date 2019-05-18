<?php

namespace Drupal\env_dependencies\EventSubscriber;

use Drupal\env_dependencies\EnvDependenciesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EnvDependenciesEventSubscriber.
 *
 * @package Drupal\env_dependencies
 */
class EnvDependenciesEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EnvDependenciesEvent::AFTER_ENABLE_DEPENDENCIES][] = [
      'doAfterEnablingConfiguration',
      800,
    ];
    return $events;
  }

  /**
   * Subscriber Callback for the event.
   *
   * @param \Drupal\env_dependencies\EnvDependenciesEvent $event
   *   EnvDependenciesEvent object.
   */
  public function doAfterEnablingConfiguration(EnvDependenciesEvent $event) {
    $config = $event->getConfig();
    // Sitemap xml fix.
    if (!empty($config->get('base_url'))) {
      \Drupal::state()->set('xmlsitemap_base_url', $config->get('base_url'));
    }
  }

}
