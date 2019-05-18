<?php

namespace Drupal\integro\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the entity class.
 *
 * @ConfigEntityType(
 *   id = "integro_connector",
 *   label = @Translation("Connector"),
 *   label_collection = @Translation("Connectors"),
 *   label_singular = @Translation("connector"),
 *   label_plural = @Translation("connectors"),
 *   label_count = @PluralTranslation(
 *     singular = "@count connector",
 *     plural = "@count connectors",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\integro\Form\ConnectorForm",
 *       "edit" = "Drupal\integro\Form\ConnectorForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\integro\ConnectorListBuilder",
 *   },
 *   admin_permission = "administer integro_connector",
 *   config_prefix = "connector",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "configuration",
 *     "integration",
 *     "client_configuration"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/integro/connectors/add",
 *     "edit-form" = "/admin/config/integro/connectors/{integro_connector}",
 *     "delete-form" = "/admin/config/integro/connectors/{integro_connector}/delete",
 *     "authorize" = "/admin/config/integro/connectors/{integro_connector}/authorize",
 *     "collection" = "/admin/config/integro/connectors"
 *   }
 * )
 */
class Connector extends ConfigEntityBase implements ConnectorInterface {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * The authorized flag.
   *
   * @var boolean
   */
  protected $authorized;

  /**
   * The auth data.
   *
   * @var array
   */
  protected $auth_data;

  /**
   * The connector configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The integration plugin ID.
   *
   * @var string
   */
  protected $integration;

  /**
   * The client configuration.
   *
   * @var array
   */
  protected $client_configuration = [];

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    return $this->getIntegration()->getDefinition()->getClientPlugin($this->client_configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getClientConfiguration() {
    return $this->client_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getIntegration() {
    return \Drupal::service('integro_integration.manager')->getIntegration($this->integration);
  }

  /**
   * {@inheritdoc}
   */
  public function auth() {
    $this->auth_data = $this->getClient()->auth($this->client_configuration);
    unset($this->auth_data['client']);

    $this->authorized = isset($this->auth_data['authorized']) && $this->auth_data['authorized'];
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupAuthData() {
    $this->authorized = FALSE;
    $this->auth_data = [];
    return $this;
  }

}
