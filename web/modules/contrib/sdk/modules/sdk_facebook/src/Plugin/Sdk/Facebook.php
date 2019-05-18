<?php

namespace Drupal\sdk_facebook\Plugin\Sdk;

use Drupal\sdk\SdkPluginBase;
use Facebook\Facebook as FacebookSdk;

/**
 * SDK definition.
 *
 * @Sdk(
 *   id = "facebook",
 *   label = @Translation("Facebook"),
 * )
 */
class Facebook extends SdkPluginBase {

  /**
   * SDK instance.
   *
   * @var FacebookSdk
   */
  private $instance;

  /**
   * {@inheritdoc}
   */
  protected function getInstance() {
    if (NULL === $this->instance) {
      $config = $this->getConfig();

      $this->instance = new FacebookSdk([
        'app_id' => $config->settings['app_id'],
        'app_secret' => $config->settings['app_secret'],
        'default_graph_version' => 'v' . (float) $config->settings['api_version'],
      ]);
    }

    return $this->instance;
  }

  /**
   * {@inheritdoc}
   */
  public function derive() {
    $instance = $this->getInstance();
    $token = $this->getToken();

    if (NULL !== $token) {
      $instance->setDefaultAccessToken($token);
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function loginUrl() {
    $config = $this->getConfig();

    return $this->getInstance()->getRedirectLoginHelper()->getLoginUrl(
      $config->getCallbackUrl(),
      $config->settings['scope']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loginCallback() {
    $token = $this->getInstance()->getRedirectLoginHelper()->getAccessToken();

    if (NULL !== $token) {
      $this->setToken($token, $token->getExpiresAt()->getTimestamp());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenExpiration() {
    $token = $this->getToken();

    // Token not set or its life has ended.
    if (NULL === $token) {
      return NULL;
    }

    $expires = $token->getExpiresAt();

    // If token has "NULL" as expiration this means it has no limitation.
    if (NULL === $expires) {
      return self::TOKEN_LIFE_UNLIMITED;
    }

    return $expires;
  }

}
