<?php

namespace Drupal\social_auth_twitch\Plugin\Network;

use Depotwarehouse\OAuth2\Client\Twitch\Provider\Twitch;
use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_twitch\Settings\TwitchAuthSettings;

/**
 * Defines a Network Plugin for Social Auth Twitch.
 *
 * @package Drupal\social_auth_twitch\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_twitch",
 *   social_network = "Twitch",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_twitch\Settings\TwitchAuthSettings",
 *       "config_id": "social_auth_twitch.settings"
 *     }
 *   }
 * )
 */
class TwitchAuth extends NetworkBase implements TwitchAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \Depotwarehouse\OAuth2\Client\Twitch\Provider\Twitch|false
   *   The initialized 3rd party library instance.
   *   False if library could not be initialized.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = 'Depotwarehouse\OAuth2\Client\Twitch\Provider\Twitch';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Twitch Library for the league oAuth not found. Class: %s.', $class_name));
    }
    /* @var \Drupal\social_auth_twitch\Settings\TwitchAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => Url::fromRoute('social_auth_twitch.callback')->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new Twitch($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_twitch\Settings\TwitchAuthSettings $settings
   *   The Twitch auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(TwitchAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();

    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_twitch')
        ->error('Define Client ID and Client Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
