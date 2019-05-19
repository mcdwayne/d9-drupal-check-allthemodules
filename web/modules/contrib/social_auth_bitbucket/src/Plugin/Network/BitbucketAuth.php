<?php

namespace Drupal\social_auth_bitbucket\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_bitbucket\Settings\BitbucketAuthSettings;
use Stevenmaguire\OAuth2\Client\Provider\Bitbucket;

/**
 * Defines a Network Plugin for Social Auth Bitbucket.
 *
 * @package Drupal\social_auth_bitbucket\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_bitbucket",
 *   social_network = "Bitbucket",
 *   type = "social_auth",
 *   handlers = {
 *    "settings": {
 *       "class": "\Drupal\social_auth_bitbucket\Settings\BitbucketAuthSettings",
 *       "config_id": "social_auth_bitbucket.settings"
 *     }
 *   }
 * )
 */
class BitbucketAuth extends NetworkBase implements BitbucketAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \Stevenmaguire\OAuth2\Client\Provider\Bitbucket|false
   *   The initialized 3rd party library instance.
   *   False if library could not be initialized.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\Stevenmaguire\OAuth2\Client\Provider\Bitbucket';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Bitbucket library for PHP League OAuth2 not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_bitbucket\Settings\BitbucketAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getKey(),
        'clientSecret' => $settings->getSecret(),
        'redirectUri' => Url::fromRoute('social_auth_bitbucket.callback')->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];

      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new Bitbucket($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_bitbucket\Settings\BitbucketAuthSettings $settings
   *   The Bitbucket auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(BitbucketAuthSettings $settings) {
    $client_id = $settings->getKey();
    $client_secret = $settings->getSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_bitbucket')
        ->error('Define Key and Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
