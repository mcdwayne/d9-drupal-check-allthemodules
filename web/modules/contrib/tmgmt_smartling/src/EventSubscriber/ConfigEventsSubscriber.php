<?php

namespace Drupal\tmgmt_smartling\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigEventsSubscriber.
 *
 * @package Drupal\tmgmt_smartling\EventSubscriber
 */
class ConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * @var CacheTagsInvalidator
   */
  private $cacheInvalidator;

  /**
   * ConfigEventsSubscriber constructor.
   *
   * @param \Drupal\Core\Cache\CacheTagsInvalidator $cacheInvalidator
   */
  public function __construct(CacheTagsInvalidator $cacheInvalidator) {
    $this->cacheInvalidator = $cacheInvalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSavingConfig'];

    return $events;
  }

  /**
   * Subscriber Callback for the event.
   * @param ConfigCrudEvent $event
   */
  public function onSavingConfig(ConfigCrudEvent $event) {
    $config_data = $event->getConfig()->get();

    if (
      !empty($config_data["plugin"]) &&
      $config_data["plugin"] == "smartling" &&
      !empty($config_data["settings"]["project_id"])
    ) {
      $this->cacheInvalidator->invalidateTags(["tmgmt_smartling:firebase_config:{$config_data["settings"]["project_id"]}"]);
    }
  }


}
