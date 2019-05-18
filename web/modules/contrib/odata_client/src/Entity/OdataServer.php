<?php

namespace Drupal\odata_client\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Odata server entity.
 *
 * @ConfigEntityType(
 *   id = "odata_server",
 *   label = @Translation("Odata server"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\odata_client\OdataServerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\odata_client\Form\OdataServerForm",
 *       "edit" = "Drupal\odata_client\Form\OdataServerForm",
 *       "delete" = "Drupal\odata_client\Form\OdataServerDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\odata_client\OdataServerHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "odata_server",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/odata_server/{odata_server}",
 *     "add-form" = "/admin/structure/odata_server/add",
 *     "edit-form" = "/admin/structure/odata_server/{odata_server}/edit",
 *     "delete-form" = "/admin/structure/odata_server/{odata_server}/delete",
 *     "collection" = "/admin/structure/odata_server"
 *   }
 * )
 */
class OdataServer extends ConfigEntityBase implements OdataServerInterface {

  /**
   * The Odata server ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Odata server label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Odata server service url.
   *
   * @var string
   */
  protected $url;

  /**
   * The Oauth web app client id.
   *
   * @var string
   */
  protected $client_id;

  /**
   * The Oauth web app secret key.
   *
   * @var string
   */
  protected $client_secret;

  /**
   * The Oauth web app redurect uri.
   *
   * @var string
   */
  protected $redirect_uri;

  /**
   * The Oauth server authorize url.
   *
   * @var string
   */
  protected $url_authorize;

  /**
   * The Oauth server access token url.
   *
   * @var string
   */
  protected $url_access_token;

  /**
   * The Oauth server access token provider.
   *
   * @var string
   */
  protected $token_provider;

  /**
   * The Oauth server access token provider.
   *
   * @var string
   */
  protected $tenant;

  /**
   * The Oauth web app resource url.
   *
   * @var string
   */
  protected $url_resource_owner_details;

  /**
   * The Odata server connection user name.
   *
   * @var string
   */
  protected $user_name;

  /**
   * The Odata server connection password.
   *
   * @var string
   */
  protected $password;

  /**
   * The Odata server connection type.
   *
   * @var string
   */
  protected $odata_type;

  /**
   * The Odata server connection default collection.
   *
   * @var string
   */
  protected $default_collection;

  /**
   * The Odata server authentication method.
   *
   * @var string
   */
  protected $authentication_method;

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientId() {
    return $this->client_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret() {
    return $this->client_secret;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUri() {
    return $this->redirect_uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlAuthorize() {
    return $this->url_authorize;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlToken() {
    return $this->url_access_token;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenProvider() {
    return $this->token_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getTenant() {
    return $this->tenant;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlResource() {
    return $this->url_resource_owner_details;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserName() {
    return $this->user_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * {@inheritdoc}
   */
  public function getOdataType() {
    return $this->odata_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultCollection() {
    return $this->default_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationMethod() {
    return $this->authentication_method;
  }

}
