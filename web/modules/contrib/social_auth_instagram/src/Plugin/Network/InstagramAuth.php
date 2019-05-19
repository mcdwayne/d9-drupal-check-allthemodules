<?php

namespace Drupal\social_auth_instagram\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_instagram\Settings\InstagramAuthSettings;
use League\OAuth2\Client\Provider\Instagram;

/**
 * Defines a Network Plugin for Social Auth Instagram.
 *
 * @package Drupal\social_auth_instagram\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_instagram",
 *   social_network = "Instagram",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_instagram\Settings\InstagramAuthSettings",
 *       "config_id": "social_auth_instagram.settings"
 *     }
 *   }
 * )
 */
class InstagramAuth extends NetworkBase implements InstagramAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \League\OAuth2\Client\Provider\Instagram|false
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\League\OAuth2\Client\Provider\Instagram';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Instagram library for PHP League OAuth2 not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_instagram\Settings\InstagramAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => Url::fromRoute('social_auth_instagram.callback')->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new Instagram($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_instagram\Settings\InstagramAuthSettings $settings
   *   The Instagram auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(InstagramAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_instagram')
        ->error('Define Client ID and Client Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
