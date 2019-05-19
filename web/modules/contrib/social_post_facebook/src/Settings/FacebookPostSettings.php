<?php

namespace Drupal\social_post_facebook\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Returns the app information.
 */
class FacebookPostSettings extends SettingsBase implements FacebookPostSettingsInterface {

  /**
   * Application ID.
   *
   * @var string
   */
  protected $appId;

  /**
   * Application secret.
   *
   * @var string
   */
  protected $appSecret;

  /**
   * The default graph version.
   *
   * @var string
   */
  protected $graphVersion;

  /**
   * The default access token.
   *
   * @var string
   */
  protected $defaultToken;

  /**
   * The redirect URL for social_auth implmeneter.
   *
   * @var string
   */
  protected $oauthRedirectUrl;

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

  /**
   * {@inheritdoc}
   */
  public function getGraphVersion() {
    if (!$this->graphVersion) {
      $this->graphVersion = $this->config->get('graph_version');
    }
    return $this->graphVersion;
  }

}
