<?php

namespace Drupal\social_auth_itsme\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth itsme settings.
 */
class ItsmeAuthSettings extends SettingsBase implements ItsmeAuthSettingsInterface {

  /**
   * The token.
   *
   * @var string
   */
  protected $token;

  /**
   * The scopes.
   *
   * @var array
   */
  protected $scopes;

  /**
   * The service.
   *
   * @var string
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    if (!$this->token) {
      $this->token = $this->config->get('token');
    }
    return $this->token;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopes() {
    if (!$this->scopes) {
      $this->scopes = array_keys(array_filter($this->config->get('scopes')));
    }
    return $this->scopes;
  }

  /**
   * {@inheritdoc}
   */
  public function getService() {
    if (!$this->service) {
      $this->service = $this->config->get('service');
    }
    return $this->service;
  }

}
