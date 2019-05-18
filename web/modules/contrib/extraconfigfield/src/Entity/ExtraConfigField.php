<?php

namespace Drupal\extraconfigfield\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\extraconfigfield\ExtraConfigFieldInterface;

/**
 * Defines the extraconfigfield entity type.
 *
 * @ConfigEntityType(
 *   id = "extraconfigfield",
 *   label = @Translation("ExtraConfigField"),
 *   handlers = {
 *     "list_builder" = "Drupal\extraconfigfield\ExtraConfigFieldListBuilder",
 *     "form" = {
 *       "add" = "Drupal\extraconfigfield\Form\ExtraConfigFieldForm",
 *       "edit" = "Drupal\extraconfigfield\Form\ExtraConfigFieldForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "extraconfigfield",
 *   admin_permission = "administer extraconfigfield",
 *   links = {
 *     "collection" = "/admin/structure/extraconfigfield",
 *     "add-form" = "/admin/structure/extraconfigfield/add",
 *     "edit-form" = "/admin/structure/extraconfigfield/{extraconfigfield}",
 *     "delete-form" = "/admin/structure/extraconfigfield/{extraconfigfield}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class ExtraConfigField extends ConfigEntityBase implements ExtraConfigFieldInterface {

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
   * The config item.
   *
   * @var string
   */
  protected $config_name;

  /**
   * The config key.
   *
   * @var string
   */
  protected $config_key;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The field name.
   *
   * @var string
   */
  protected $field_name;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entity_type . '.' . $this->bundle . '.' . $this->field_name;
  }

  public function preSave(EntityStorageInterface $storage) {
    $this->id = $this->id();
    parent::preSave($storage);
  }

  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('config', $this->config_name);
    return $this;
  }


}
