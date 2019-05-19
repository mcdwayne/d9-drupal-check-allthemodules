<?php

namespace Drupal\social_auth_pbs\Plugin\Network;

use OpenPublicMedia\OAuth2\Client\Provider\Pbs;
use Drupal\Core\Url;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_pbs\Settings\PbsAuthSettings;

/**
 * Defines a Network Plugin for Social Auth PBS.
 *
 * @package Drupal\social_auth_pbs\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_pbs",
 *   social_network = "PBS",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_pbs\Settings\PbsAuthSettings",
 *       "config_id": "social_auth_pbs.settings"
 *     }
 *   }
 * )
 */
class PbsAuth extends NetworkBase implements PbsAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \OpenPublicMedia\OAuth2\Client\Provider\Pbs|bool
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = 'OpenPublicMedia\OAuth2\Client\Provider\Pbs';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PBS library for PHP 
        League OAuth2 not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_pbs\Settings\PbsAuthSettings $settings */
    $settings = $this->settings;
    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => Url::fromRoute('social_auth_pbs.callback')->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new Pbs($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_pbs\Settings\PbsAuthSettings $settings
   *   The PBS auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(PbsAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_pbs')
        ->error('Define Client ID and Client Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }

}
