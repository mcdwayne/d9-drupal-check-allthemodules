<?php

namespace Drupal\odata_client\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Odata server entities.
 */
interface OdataServerInterface extends ConfigEntityInterface {

  /**
   * Return the OData connection url.
   *
   * @return string
   *   The OData server connection url.
   */
  public function getUrl();

  /**
   * Return the Oauth web app client id.
   *
   * @return string
   *   The Oauth web app client id.
   */
  public function getClientId();

  /**
   * Return the Oauth web app client secret key.
   *
   * @return string
   *   The Oauth web app secret key.
   */
  public function getClientSecret();

  /**
   * Return the Oauth web app redirect uri.
   *
   * @return string
   *   The Oauth web app redirect uri.
   */
  public function getRedirectUri();

  /**
   * Return the Oauth server authorize url.
   *
   * @return string
   *   The Oauth server authorize url.
   */
  public function getUrlAuthorize();

  /**
   * Return the Oauth server token url.
   *
   * @return string
   *   The Oauth server token url.
   */
  public function getUrlToken();
  
  /**
   * Return the Oauth server token provider.
   *
   * @return string
   *   The Oauth server token provider.
   */
  public function getTokenProvider();

  /**
   * Return the Azure server client id (tenant).
   *
   * @return string
   *   The Azure server client id (tenant).
   */
  public function getTenant();

  /**
   * Return the Oauth web app resource url.
   *
   * @return string
   *   The Oauth web app resource url.
   */
  public function getUrlResource();

  /**
   * Return the OData connection user name.
   *
   * @return string
   *   The OData server connection user name.
   */
  public function getUserName();

  /**
   * Return the OData connection password.
   *
   * @return string
   *   The OData server connection password.
   */
  public function getPassword();

  /**
   * Return the OData connection type.
   *
   * @return string
   *   The OData server connection type.
   */
  public function getOdataType();

  /**
   * Return the OData connection default collection.
   *
   * @return string
   *   The OData server connection default collection.
   */
  public function getDefaultCollection();

  /**
   * Return the OData connection default collection.
   *
   * @return string
   *   The OData server connection default collection.
   */
  public function getAuthenticationMethod();

}
