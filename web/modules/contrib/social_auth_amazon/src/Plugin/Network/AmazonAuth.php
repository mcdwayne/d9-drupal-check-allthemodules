<?php

namespace Drupal\social_auth_amazon\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_amazon\Settings\AmazonAuthSettings;
use Luchianenco\OAuth2\Client\Provider\Amazon;

/**
 * Defines a Network Plugin for Social Auth Amazon.
 *
 * @package Drupal\social_auth_amazon\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_amazon",
 *   social_network = "Amazon",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_amazon\Settings\AmazonAuthSettings",
 *       "config_id": "social_auth_amazon.settings"
 *     }
 *   }
 * )
 */
class AmazonAuth extends NetworkBase implements AmazonAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \Luchianenco\OAuth2\Client\Provider\Amazon|false
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\Luchianenco\OAuth2\Client\Provider\Amazon';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Amazon library for PHP League OAuth2 not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_amazon\Settings\AmazonAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => Url::fromRoute('social_auth_amazon.callback')->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings = [
          'proxy' => $proxyUrl,
        ];
      }

      return new Amazon($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_amazon\Settings\AmazonAuthSettings $settings
   *   The Amazon auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(AmazonAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_amazon')
        ->error('Define Client ID and Client Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
