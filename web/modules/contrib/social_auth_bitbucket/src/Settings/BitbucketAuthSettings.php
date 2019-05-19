<?php

namespace Drupal\social_auth_bitbucket\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Bitbucket settings.
 */
class BitbucketAuthSettings extends SettingsBase implements BitbucketAuthSettingsInterface {

  /**
   * Key.
   *
   * @var string
   */
  protected $key;

  /**
   * Secret.
   *
   * @var string
   */
  protected $secret;

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    if (!$this->key) {
      $this->key = $this->config->get('key');
    }
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecret() {
    if (!$this->secret) {
      $this->secret = $this->config->get('secret');
    }
    return $this->secret;
  }

}
