<?php

namespace Drupal\virtual_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\virtual_entities\VirtualEntityTypeInterface;

/**
 * Defines the Virtual entity type entity.
 *
 * @ConfigEntityType(
 *   id = "virtual_entity_type",
 *   label = @Translation("Virtual entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\virtual_entities\VirtualEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\virtual_entities\Form\VirtualEntityTypeForm",
 *       "edit" = "Drupal\virtual_entities\Form\VirtualEntityTypeForm",
 *       "delete" = "Drupal\virtual_entities\Form\VirtualEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\virtual_entities\VirtualEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "virtual_entity_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "virtual_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/virtual_entity_type/{virtual_entity_type}",
 *     "add-form" = "/admin/structure/virtual_entity_type/add",
 *     "edit-form" = "/admin/structure/virtual_entity_type/{virtual_entity_type}/edit",
 *     "delete-form" = "/admin/structure/virtual_entity_type/{virtual_entity_type}/delete",
 *     "collection" = "/admin/structure/virtual_entity_type"
 *   }
 * )
 */
class VirtualEntityType extends ConfigEntityBundleBase implements VirtualEntityTypeInterface {
  /**
   * The Virtual entity type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Virtual entity type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this entity type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating a entity of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * The endpoint of this entity type.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * The entities identity.
   *
   * @var string
   */
  protected $entities_identity = '';

  /**
   * The parameters for the endpoint.
   *
   * @var array
   */
  protected $parameters = [];

  /**
   * The external entity storage client id.
   *
   * @var string
   */
  protected $client = 'virtual_entity_storage_client_plugin_restful';

  /**
   * The format in which to make the requests for this entity type.
   *
   * For example: 'json'.
   *
   * @var string
   */
  protected $format = 'json';

  /**
   * The field mappings for this virtual entity type.
   *
   * @var array
   */
  protected $field_mappings = [];

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndPoint() {
    return $this->endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesIdentity() {
    return $this->entities_identity;
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat() {
    return $this->format;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMappings() {
    return $this->field_mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapping($field_name) {
    return isset($this->field_mappings[$field_name]) ? $this->field_mappings[$field_name] : FALSE;
  }

}
