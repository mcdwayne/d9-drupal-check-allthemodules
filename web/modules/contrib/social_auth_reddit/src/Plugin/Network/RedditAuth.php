<?php

namespace Drupal\social_auth_reddit\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_reddit\Settings\RedditAuthSettings;
use Rudolf\OAuth2\Client\Provider\Reddit;

/**
 * Defines a Network Plugin for Social Auth Reddit.
 *
 * @package Drupal\social_auth_reddit\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_reddit",
 *   social_network = "Reddit",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_reddit\Settings\RedditAuthSettings",
 *       "config_id": "social_auth_reddit.settings"
 *     }
 *   }
 * )
 */
class RedditAuth extends NetworkBase implements RedditAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \Rudolf\OAuth2\Client\Provider\Reddit|false
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {
    $class_name = '\Rudolf\OAuth2\Client\Provider\Reddit';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Reddit Library for the League OAuth not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_reddit\Settings\RedditAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => Url::fromRoute('social_auth_reddit.callback')->setAbsolute()->toString(),
        'userAgent' => $settings->getUserAgentString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new Reddit($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_reddit\Settings\RedditAuthSettings $settings
   *   The Reddit auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(RedditAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    $user_agent = $settings->getUserAgentString();
    if (!$client_id || !$client_secret || !$user_agent) {
      $this->loggerFactory
        ->get('social_auth_reddit')
        ->error('Define Client ID, Client Secret, and User Agent on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
