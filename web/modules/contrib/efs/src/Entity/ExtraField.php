<?php

namespace Drupal\efs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Extra field entity.
 *
 * @ConfigEntityType(
 *   id = "extra_field",
 *   label = @Translation("Extra field"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\efs\ExtraFieldListBuilder",
 *     "form" = {
 *       "add" = "Drupal\efs\Form\ExtraFieldForm",
 *       "edit" = "Drupal\efs\Form\ExtraFieldForm",
 *       "delete" = "Drupal\efs\Form\ExtraFieldDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\efs\ExtraFieldHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "extra_field",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/extra_field/{extra_field}",
 *     "add-form" = "/admin/structure/extra_field/add",
 *     "edit-form" = "/admin/structure/extra_field/{extra_field}/edit",
 *     "delete-form" = "/admin/structure/extra_field/{extra_field}/delete",
 *     "collection" = "/admin/structure/extra_field"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "field_name",
 *     "entity_type",
 *     "bundle",
 *     "context",
 *     "mode",
 *     "plugin",
 *     "weight",
 *     "settings"
 *   }
 * )
 */
class ExtraField extends ConfigEntityBase implements ExtraFieldInterface {

  /**
   * The field ID.
   *
   * The ID consists of 2 parts: the entity type and the field name.
   *
   * Example: node.body, user.field_main_image.
   *
   * @var string
   */
  protected $id;

  /**
   * The Extra field label.
   *
   * @var string
   */
  protected $label;

  /**
   * The field name.
   *
   * This is the name of the property under which the field values are placed in
   * an entity: $entity->{$field_name}. The maximum length is
   * Field:NAME_MAX_LENGTH.
   *
   * Example: body, field_main_image.
   *
   * @var string
   */
  protected $field_name;

  /**
   * The name of the entity type the field can be attached to.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The name of the bundle the field can be attached to.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The type of context form/display.
   *
   * @var string
   */
  protected $context;

  /**
   * The name of the view_mode or form_mode.
   *
   * @var string
   */
  protected $mode;

  /**
   * The name of the plugin.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The weight with respect to other efs fields in the same display.
   *
   * @var int
   */
  protected $weight;

  /**
   * The settings configuration.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public function composedId() {
    return implode('.', [
      $this->getTargetEntityTypeId(),
      $this->getBundle(),
      $this->getContext(),
      $this->getMode(),
      $this->getName(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * {@inheritdoc}
   */
  public function getMode() {
    return $this->mode;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting_name) {
    return $this->settings[$setting_name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($setting_name, $value) {
    $this->settings[$setting_name] = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings + $this->settings;
    return $this;
  }

}
