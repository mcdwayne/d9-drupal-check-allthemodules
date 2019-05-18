<?php

namespace Drupal\sdk_instagram\Plugin\Sdk;

use Drupal\sdk\SdkPluginBase;
use MetzWeb\Instagram\Instagram as InstagramSdk;

/**
 * SDK definition.
 *
 * @Sdk(
 *   id = "instagram",
 *   label = @Translation("Instagram"),
 * )
 */
class Instagram extends SdkPluginBase {

  /**
   * SDK instance.
   *
   * @var InstagramSdk
   */
  private $instance;

  /**
   * {@inheritdoc}
   */
  protected function getInstance() {
    if (NULL === $this->instance) {
      $config = $this->getConfig();

      $this->instance = new InstagramSdk([
        'apiKey' => $config->settings['client_id'],
        'apiSecret' => $config->settings['client_secret'],
        'apiCallback' => $config->getCallbackUrl(),
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
      $instance->setAccessToken($token);
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function loginUrl() {
    return $this->getInstance()->getLoginUrl($this->getConfig()->settings['scope']);
  }

  /**
   * {@inheritdoc}
   */
  public function loginCallback() {
    if (isset($_GET['code'])) {
      $this->setToken($this->getInstance()->getOAuthToken($_GET['code']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenExpiration() {
    return self::TOKEN_LIFE_UNLIMITED;
  }

}
