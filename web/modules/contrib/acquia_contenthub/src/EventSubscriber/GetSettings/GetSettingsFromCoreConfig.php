<?php

namespace Drupal\acquia_contenthub\EventSubscriber\GetSettings;

use Acquia\ContentHubClient\Settings;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\AcquiaContentHubSettingsEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Gets the ContentHub Server settings from configuration.
 */
class GetSettingsFromCoreConfig implements EventSubscriberInterface {

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * GetSettingsFromCoreConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::GET_SETTINGS][] = 'onGetSettings';
    return $events;
  }

  /**
   * Extract settings from configuration and create a Settings object.
   *
   * @param \Drupal\acquia_contenthub\Event\AcquiaContentHubSettingsEvent $event
   *   The dispatched event.
   *
   * @see \Acquia\ContentHubClient\Settings
   */
  public function onGetSettings(AcquiaContentHubSettingsEvent $event) {
    $config = $this->configFactory->get('acquia_contenthub.admin_settings');

    $settings = new Settings($config->get('client_name'), $config->get('origin'), $config->get('api_key'), $config->get('secret_key'), $config->get('hostname'), $config->get('shared_secret'), $config->get('webhook'));
    if ($settings) {
      $event->setSettings($settings);
      $event->setProvider('core_config');
      $event->stopPropagation();
    }
  }

}
