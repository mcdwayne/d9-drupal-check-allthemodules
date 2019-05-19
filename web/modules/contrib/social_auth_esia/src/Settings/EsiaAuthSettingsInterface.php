<?php

namespace Drupal\social_auth_esia\Settings;

/**
 * Defines the settings interface.
 */
interface EsiaAuthSettingsInterface {

  /**
   * Gets the client ID.
   *
   * @return mixed
   *   The client ID.
   */
  public function getClientId();

  /**
   * Gets the testing server indicator.
   *
   * @return bool
   *   The testing server indicator.
   */
  public function getUseTestingServer();

  /**
   * Gets certificate path.
   *
   * @return string
   *   The certificate path.
   */
  public function getCertificatePath();

  /**
   * Gets private key path.
   *
   * @return string
   *   The private key path.
   */
  public function getPrivateKeyPath();

  /**
   * Gets private key password.
   *
   * @return string
   *   The private key password.
   */
  public function getPrivateKeyPassword();

  /**
   * Gets scopes.
   *
   * @return string
   *   The scopes.
   */
  public function getScopes();

}
