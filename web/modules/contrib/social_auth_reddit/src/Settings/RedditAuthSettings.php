<?php

namespace Drupal\social_auth_reddit\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Reddit settings.
 */
class RedditAuthSettings extends SettingsBase implements RedditAuthSettingsInterface {
  /**
   * Client ID.
   *
   * @var string
   */
  protected $clientId;
  /**
   * Client secret.
   *
   * @var string
   */
  protected $clientSecret;
  /**
   * The data point to be collected.
   *
   * @var string
   */
  protected $scopes;
  /**
   * User Agent string.
   *
   * @var string
   */
  protected $userAgentString;

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
   * {@inheritdoc}
   */
  public function getScopes() {
    if (!$this->scopes) {
      $this->scopes = $this->config->get('scopes');
    }
    return $this->scopes;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserAgentString() {
    if (!$this->userAgentString) {
      $this->userAgentString = $this->config->get('user_agent_string');
    }
    return $this->userAgentString;
  }

}
