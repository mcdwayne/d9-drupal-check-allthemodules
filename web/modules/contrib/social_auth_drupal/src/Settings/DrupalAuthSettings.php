<?php

namespace Drupal\social_auth_drupal\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Drupal settings.
 */
class DrupalAuthSettings extends SettingsBase implements DrupalAuthSettingsInterface {

  /**
   * Client ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   * Client secret.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * Base Url
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * {@inheritdoc}
   */
  public function getClientId() {
    if (!$this->clientId) {
      $this->clientId = $this->config->get('client_id');
    }
    return $this->clientId;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret() {
    if (!$this->clientSecret) {
      $this->clientSecret = $this->config->get('client_secret');
    }
    return $this->clientSecret;
  }
  /**
   * {@inheritdoc}
   */
  public function getBaseUrl() {
    if (!$this->BaseUrl) {
      $this->baseUrl = $this->config->get('base_url');
    }
    return $this->baseUrl;
  }
}
