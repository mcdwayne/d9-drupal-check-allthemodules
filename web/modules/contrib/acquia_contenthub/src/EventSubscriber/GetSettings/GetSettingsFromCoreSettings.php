<?php

namespace Drupal\acquia_contenthub\EventSubscriber\GetSettings;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\AcquiaContentHubSettingsEvent;
use Drupal\Core\Site\Settings as CoreSettings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Acquia\ContentHubClient\Settings as ContentHubClientSettings;

/**
 * Gets the ContentHub Server settings from Drupal's settings.
 */
class GetSettingsFromCoreSettings implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::GET_SETTINGS][] = ['onGetSettings', 1000];
    return $events;
  }

  /**
   * Gets a prebuilt Settings object from Drupal's settings file.
   *
   * @param \Drupal\acquia_contenthub\Event\AcquiaContentHubSettingsEvent $event
   *   The dispatched event.
   *
   * @see \Acquia\ContentHubClient\Settings
   */
  public function onGetSettings(AcquiaContentHubSettingsEvent $event) {
    $settings = CoreSettings::get('acquia_contenthub.settings');
    if ($settings && $settings instanceof ContentHubClientSettings) {
      $event->setSettings($settings);
      $event->setProvider('core_settings');
      $event->stopPropagation();
    }
  }

}
