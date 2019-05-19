<?php

namespace Drupal\social_auth_esia\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth ESIA app settings.
 */
class EsiaAuthSettings extends SettingsBase implements EsiaAuthSettingsInterface {

  /**
   * Client ID (mnemonic)
   *
   * @var string
   */
  protected $clientId;

  /**
   * The indicator for using testing server.
   *
   * @var bool
   */
  protected $useTestingServer;

  /**
   * The certificate path.
   *
   * @var string
   */
  protected $certificatePath;

  /**
   * The private key path.
   *
   * @var string
   */
  protected $privateKeyPath;

  /**
   * The private key password.
   *
   * @var string
   */
  protected $privateKeyPassword;

  /**
   * The scopes.
   *
   * @var string
   */
  protected $scopes;

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
  public function getUseTestingServer() {
    if (!$this->useTestingServer) {
      $this->useTestingServer = (bool) $this->config->get('use_testing_server');
    }

    return $this->useTestingServer;
  }

  /**
   * {@inheritdoc}
   */
  public function getCertificatePath() {
    if (!$this->certificatePath) {
      $this->certificatePath = $this->config->get('certificate_path');
    }

    return $this->certificatePath;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivateKeyPath() {
    if (!$this->privateKeyPath) {
      $this->privateKeyPath = $this->config->get('private_key_path');
    }

    return $this->privateKeyPath;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivateKeyPassword() {
    if (!$this->privateKeyPassword) {
      $this->privateKeyPassword = $this->config->get('private_key_pass');
    }

    return $this->privateKeyPassword;
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

}
