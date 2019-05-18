<?php

namespace Drupal\bibcite_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Reference type entity.
 *
 * @ConfigEntityType(
 *   id = "bibcite_reference_type",
 *   label = @Translation("Reference type"),
 *   handlers = {
 *     "access" = "Drupal\bibcite_entity\ReferenceTypeAccessControlHandler",
 *     "list_builder" = "Drupal\bibcite_entity\ReferenceTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bibcite_entity\Form\ReferenceTypeForm",
 *       "edit" = "Drupal\bibcite_entity\Form\ReferenceTypeForm",
 *       "delete" = "Drupal\bibcite_entity\Form\ReferenceTypeDeleteForm"
 *     },
 *   },
 *   config_prefix = "bibcite_reference_type",
 *   bundle_of = "bibcite_reference",
 *   admin_permission = "administer bibcite_reference",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/bibcite/settings/reference/types/add",
 *     "edit-form" = "/admin/config/bibcite/settings/reference/types/{bibcite_reference_type}",
 *     "delete-form" = "/admin/config/bibcite/settings/reference/types/{bibcite_reference_type}/delete",
 *     "collection" = "/admin/config/bibcite/settings/reference/types"
 *   }
 * )
 */
class ReferenceType extends ConfigEntityBundleBase implements ReferenceTypeInterface {

  /**
   * The Reference type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Reference type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Reference type description.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(string $desc) {
    $this->description = $desc;
    return $this;
  }

  /**
   * The Reference type override flag.
   *
   * @var bool
   */
  protected $override = FALSE;

  /**
   * The Reference fields configuration.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $fields) {
    $this->fields = $fields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequiredOverride() {
    return $this->override;
  }

}
