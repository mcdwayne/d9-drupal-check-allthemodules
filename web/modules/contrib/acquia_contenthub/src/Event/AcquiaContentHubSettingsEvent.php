<?php

namespace Drupal\acquia_contenthub\Event;

use Acquia\ContentHubClient\Settings;

use Symfony\Component\EventDispatcher\Event;

/**
 * The event dispatched to find settings for the ContentHub Service.
 */
class AcquiaContentHubSettingsEvent extends Event {

  /**
   * The ContentHubClient settings object.
   *
   * @var \Acquia\ContentHubClient\Settings
   */
  protected $settings;

  /**
   * The provider of the settings configuration.
   *
   * @var string
   */
  protected $provider;

  /**
   * Gets the ContentHubClient settings object.
   *
   * @return \Acquia\ContentHubClient\Settings
   *   The Content Hub client settings.
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Set the ContentHubClient settings object.
   *
   * @param \Acquia\ContentHubClient\Settings $settings
   *   The Content Hub client settings.
   */
  public function setSettings(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * Gets the providers of the settings object.
   *
   * @return string
   *   The Provider.
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * Sets the provider of the settings object.
   *
   * @param string $provider
   *   The Provider.
   */
  public function setProvider($provider) {
    $this->provider = $provider;
  }

}
