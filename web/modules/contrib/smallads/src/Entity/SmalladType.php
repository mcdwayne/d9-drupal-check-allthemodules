<?php

namespace Drupal\smallads\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the smallad-type entity.
 *
 * @ConfigEntityType(
 *   id = "smallad_type",
 *   label = @Translation("Small ad type"),
 *   handlers = {
 *     "access" = "Drupal\smallads\SmalladTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\smallads\Form\SmalladTypeForm",
 *       "add" = "Drupal\smallads\Form\SmalladTypeForm",
 *       "edit" = "Drupal\smallads\Form\SmalladTypeForm",
 *       "delete" = "Drupal\smallads\Form\SmalladTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\smallads\SmalladTypeListBuilder"
 *   },
 *   admin_permission = "administer site configuration",
 *   translatable = TRUE,
 *   config_prefix = "type",
 *   bundle_of = "smallad",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/smallads/manage/{smallad_type}/delete",
 *     "edit-form" = "/admin/structure/smallads/manage/{smallad_type}",
 *     "add-form" = "/admin/structure/smallads/types/add",
 *     "collection" = "/admin/structure/smallads/types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "label_plural",
 *     "description",
 *     "weight",
 *   }
 * )
 */
class SmalladType extends ConfigEntityBundleBase {

  /**
   * The smallad type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The smallad type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The smallad type label.
   *
   * @var string
   */
  protected $label_plural;

  /**
   * The description of the smallad type.
   *
   * @var string
   */
  protected $description;

  /**
   * The weight of the smallad type affects its default appearance in main menu.
   *
   * @var string
   */
  protected $weight;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function labelPlural() {
    return $this->label_plural;
  }

}
