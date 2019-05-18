<?php

namespace Drupal\sdk_linkedin\Plugin\Sdk;

use Drupal\sdk\SdkPluginBase;
use Happyr\LinkedIn\LinkedIn as LinkedInSdk;

/**
 * SDK definition.
 *
 * @Sdk(
 *   id = "linkedin",
 *   label = @Translation("LinkedIn"),
 * )
 */
class LinkedIn extends SdkPluginBase {

  /**
   * SDK instance.
   *
   * @var LinkedInSdk
   */
  private $instance;

  /**
   * {@inheritdoc}
   */
  protected function getInstance() {
    if (NULL === $this->instance) {
      $config = $this->getConfig();

      $this->instance = new LinkedInSdk(
        $config->settings['client_id'],
        $config->settings['client_secret']
      );
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
      $instance->setAccessToken($token);
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function loginUrl() {
    $config = $this->getConfig();

    return $this->getInstance()->getLoginUrl([
      'scope' => $config->settings['scope'],
      'redirect_uri' => $config->getCallbackUrl(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function loginCallback() {
    try {
      $token = $this->getInstance()->getAccessToken();
      $this->setToken($token, $token->getExpiresAt()->getTimestamp());
    }
    catch (\Exception $e) {
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
