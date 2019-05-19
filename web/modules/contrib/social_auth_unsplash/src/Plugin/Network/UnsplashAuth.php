<?php

namespace Drupal\social_auth_unsplash\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_unsplash\Settings\UnsplashAuthSettings;
use Unsplash\OAuth2\Client\Provider\Unsplash;

/**
 * Defines a Network Plugin for Social Auth Unsplash.
 *
 * @package Drupal\social_auth_unsplash\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_unsplash",
 *   social_network = "Unsplash",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_unsplash\Settings\UnsplashAuthSettings",
 *       "config_id": "social_auth_unsplash.settings"
 *     }
 *   }
 * )
 */
class UnsplashAuth extends NetworkBase implements UnsplashAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \Unsplash\OAuth2\Client\Provider\Unsplash|false
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\Unsplash\OAuth2\Client\Provider\Unsplash';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Unsplash library for PHP League OAuth2 not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_unsplash\Settings\UnsplashAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => Url::fromRoute('social_auth_unsplash.callback')->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];

      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new Unsplash($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_unsplash\Settings\UnsplashAuthSettings $settings
   *   The Unsplash auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(UnsplashAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_unsplash')
        ->error('Define Client ID and Client Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
