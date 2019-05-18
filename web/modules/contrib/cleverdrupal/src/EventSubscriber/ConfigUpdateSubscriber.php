<?php

namespace Drupal\cleverreach\EventSubscriber;

use Drupal\cleverreach\Component\Utility\EventHandler;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listener for site name change. Sync task must be enqueued when site name tag is changed.
 */
class ConfigUpdateSubscriber implements EventSubscriberInterface {
  const CONFIG_GROUP = 'system.site';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onRespond'];
    return $events;
  }

  /**
   * Event observer when configuration is changed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   */
  public function onRespond(ConfigCrudEvent $event) {
    if (
        $event->getConfig()->getName() === self::CONFIG_GROUP &&
        $event->getConfig()->getOriginal('name') !== $event->getConfig()->get('name')
    ) {
      EventHandler::siteNameUpdate($event->getConfig()->get('name'), $event->getConfig()->getOriginal('name'));
    }
  }

}
