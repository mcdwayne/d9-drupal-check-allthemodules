<?php

namespace Drupal\section_workflow\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Deletes the cache if section workflow config has changed.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * Causes the config cache to be rebuilt.
   *
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    if (strpos($event->getConfig()->getName(), 'section_workflow.sections.') !== false ) {
      // Clear cache since we will have update the config.
      \Drupal::cache()->delete('cache_section_workflow_config_all');
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave'];
    return $events;

  }

}
