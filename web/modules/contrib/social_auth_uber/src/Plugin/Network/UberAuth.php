<?php

namespace Drupal\social_auth_uber\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_uber\Settings\UberAuthSettings;
use Stevenmaguire\OAuth2\Client\Provider\Uber;

/**
 * Defines a Network Plugin for Social Auth Uber.
 *
 * @package Drupal\social_aut_uber\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_uber",
 *   social_network = "Uber",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_uber\Settings\UberAuthSettings",
 *       "config_id": "social_auth_uber.settings"
 *     }
 *   }
 * )
 */
class UberAuth extends NetworkBase implements UberAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \Stevenmaguire\OAuth2\Client\Provider\Uber|false
   *   The initialized 3rd party library instance.
   *   False if library could not be initialized.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = 'Stevenmaguire\OAuth2\Client\Provider\Uber';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Uber library for PHP League OAuth2 not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_uber\Settings\UberAuthSettings $settings */
    $settings = $this->settings;
    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => Url::fromRoute('social_auth_uber.callback')->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new Uber($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_uber\Settings\UberAuthSettings $settings
   *   The Uber auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(UberAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_uber')
        ->error('Define Client ID and Client Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
