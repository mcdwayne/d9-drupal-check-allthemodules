<?php

namespace Drupal\social_auth_microsoft\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Microsoft settings.
 */
class MicrosoftAuthSettings extends SettingsBase implements MicrosoftAuthSettingsInterface {

  /**
   * App ID.
   *
   * @var string
   */
  protected $appId;

  /**
   * App secret.
   *
   * @var string
   */
  protected $appSecret;

  /**
   * {@inheritdoc}
   */
  public function getAppId() {
    if (!$this->appId) {
      $this->appId = $this->config->get('app_id');
    }
    return $this->appId;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppSecret() {
    if (!$this->appSecret) {
      $this->appSecret = $this->config->get('app_secret');
    }
    return $this->appSecret;
  }

}
