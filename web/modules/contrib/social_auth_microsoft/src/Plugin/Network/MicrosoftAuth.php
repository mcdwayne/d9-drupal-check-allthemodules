<?php

namespace Drupal\social_auth_microsoft\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_microsoft\Settings\MicrosoftAuthSettings;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;

/**
 * Defines a Network Plugin for Social Auth Microsoft.
 *
 * @package Drupal\simple_microsoft_connect\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_microsoft",
 *   social_network = "Microsoft",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_microsoft\Settings\MicrosoftAuthSettings",
 *       "config_id": "social_auth_microsoft.settings"
 *     }
 *   }
 * )
 */
class MicrosoftAuth extends NetworkBase implements MicrosoftAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \Stevenmaguire\OAuth2\Client\Provider\Microsoft|false
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = 'Stevenmaguire\OAuth2\Client\Provider\Microsoft';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Microsoft library for PHP League OAuth2 not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_microsoft\Settings\MicrosoftAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getAppId(),
        'clientSecret' => $settings->getAppSecret(),
        'redirectUri' => Url::fromRoute('social_auth_microsoft.callback')->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new Microsoft($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_microsoft\Settings\MicrosoftAuthSettings $settings
   *   The Microsoft auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(MicrosoftAuthSettings $settings) {
    $app_id = $settings->getAppId();
    $app_secret = $settings->getAppSecret();
    if (!$app_id || !$app_secret) {
      $this->loggerFactory
        ->get('social_auth_microsoft')
        ->error('Define App ID and App Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
