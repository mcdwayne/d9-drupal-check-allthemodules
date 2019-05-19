<?php

namespace Drupal\social_auth_ok\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Odnoklassniki app settings.
 */
class OdnoklassnikiAuthSettings extends SettingsBase implements OdnoklassnikiAuthSettingsInterface  {

  /**
   * Client ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   *
   * Client public key
   *
   * @var string
   */
  protected $clientPublic;

  /**
   * Client secret.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * The redirect URL for social_auth implmeneter.
   *
   * @var string
   */
  protected $oauthRedirectUri;

  /**
   * {@inheritdoc}
   */
  public function getClientId() {
    if (!$this->clientId) {
      $this->clientId = $this->config->get('client_id');
    }
    return $this->clientId;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret() {
    if (!$this->clientSecret) {
      $this->clientSecret = $this->config->get('client_secret');
    }
    return $this->clientSecret;
  }

  /**
   * Gets the client public key
   *
   * @return string
   *   The client public key
   */
  public function getClientPublic()
  {
    if (!$this->clientPublic) {
      $this->clientPublic = $this->config->get('client_public');
    }
    return $this->clientPublic;
  }
}
