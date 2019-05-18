<?php

namespace Drupal\sdk_github\Plugin\Sdk;

use Drupal\sdk\SdkPluginBase;
use Github\Client as GithubSdk;
use League\OAuth2\Client\Provider\Github as OAuth;

/**
 * SDK definition.
 *
 * @Sdk(
 *   id = "github",
 *   label = @Translation("GitHub"),
 * )
 */
class GitHub extends SdkPluginBase {

  /**
   * SDK instance.
   *
   * @var GithubSdk
   */
  private $instance;
  /**
   * SDK OAuth.
   *
   * @var OAuth
   */
  private $oauth;

  /**
   * {@inheritdoc}
   */
  protected function getInstance() {
    if (NULL === $this->instance) {
      $this->instance = new GithubSdk();
    }

    return $this->instance;
  }

  /**
   * Returns an instance of OAuth.
   *
   * @return OAuth
   *   OAuth instance.
   */
  protected function getOauth() {
    if (NULL === $this->oauth) {
      $config = $this->getConfig();

      $this->oauth = new OAuth([
        'clientId' => $config->settings['client_id'],
        'clientSecret' => $config->settings['client_secret'],
        'redirectUri' => $config->getCallbackUrl(),
      ]);
    }

    return $this->oauth;
  }

  /**
   * {@inheritdoc}
   */
  public function derive() {
    $instance = $this->getInstance();
    $token = $this->getToken();

    if (NULL !== $token) {
      $instance->authenticate($token->getToken(), $instance::AUTH_HTTP_TOKEN);
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function loginUrl() {
    // @todo Replace by "datetime.time" service once support of core 8.2.x will be dropped off.
    $_SESSION[static::class] = REQUEST_TIME;

    return $this->getOauth()->getAuthorizationUrl([
      'state' => $_SESSION[static::class],
      'scope' => $this->getConfig()->settings['scope'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function loginCallback() {
    if (isset($_GET['code'], $_GET['state']) && $_GET['state'] === $_SESSION[static::class]) {
      $token = $this->getOauth()->getAccessToken('authorization_code', [
        'code' => $_GET['code'],
      ]);

      $this->setToken($token, $token->getExpires());
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

    $expires = $token->getExpires();

    // If token has "NULL" as expiration this means it has no limitation.
    if (NULL === $expires) {
      return self::TOKEN_LIFE_UNLIMITED;
    }

    return $expires;
  }

}
