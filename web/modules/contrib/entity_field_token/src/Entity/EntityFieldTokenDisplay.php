<?php

namespace Drupal\entity_field_token\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\ConfigEntityType;

/**
 * Define entity field token configuration.
 *
 * @ConfigEntityType(
 *   id = "entity_field_token",
 *   label = @Translation("Field Token"),
 *   config_prefix = "field_token_display",
 *   admin_permission = "administer entity field token",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   handlers = {
 *     "form": {
 *       "add": "\Drupal\entity_field_token\Form\EntityFieldTokenForm",
 *       "edit": "\Drupal\entity_field_token\Form\EntityFieldTokenForm",
 *       "delete": "\Drupal\entity_field_token\Form\EntityFieldTokenDeleteForm"
 *     },
 *     "list_builder": "\Drupal\entity_field_token\Controller\EntityFieldTokenList",
 *     "route_provider": {
 *       "html": "\Drupal\entity_field_token\Entity\Routing\EntityFieldTokenRouteProvider"
 *     }
 *   },
 *   links = {
 *     "collection" = "/admin/config/system/entity-field-token",
 *     "add-form" = "/admin/config/system/entity-field-token/add",
 *     "edit-form" = "/admin/config/system/entity-field-token/{entity_field_token}",
 *     "delete-form" = "/admin/config/system/entity-field-token/{entity_field_token}/delete"
 *   }
 * )
 */
class EntityFieldTokenDisplay extends ConfigEntityBase {

  /**
   * Field token identifier.
   *
   * @var string
   */
  public $id;

  /**
   * Field token label.
   *
   * @var string
   */
  public $label;

  /**
   * Field token description.
   *
   * @var string
   */
  public $description;

  /**
   * Field token entity type.
   *
   * @var string
   */
  public $entity_type;

  /**
   * Field token bundles.
   *
   * @var array
   */
  public $bundles = [];

  /**
   * Field token view modes.
   *
   * @var array
   */
  public $view_modes = [];

  /**
   * Field token field type.
   *
   * @var string
   */
  public $field_type;

  /**
   * Field token field type.
   *
   * @var string
   */
  public $field_value;

  /**
   * Determine if an entity exist.
   *
   * @param $id
   *   An entity identifier.
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function entityExist($id) {
    return (bool) $this->getQuery()
      ->condition('id', $id)
      ->execute();
  }
  /**
   * Get entity query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getQuery() {
    return $this->getStorage()->getQuery();
  }
  /**
   * Get entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getStorage() {
    return $this
      ->entityTypeManager()
      ->getStorage($this->getEntityTypeId());
  }
  /**
   * Entity field manager.
   *
   * @return EntityFieldManagerInterface
   */
  protected function entityFieldManager() {
    return \Drupal::service('entity_field.manager');
  }
}
