<?php

namespace Drupal\state_form_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines a State configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "state_form_entity",
 *   label = @Translation("State form entity"),
 *   handlers = {
 *     "list_builder" = "Drupal\state_form_entity\StateFormEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\state_form_entity\Form\StateFormEntityForm",
 *       "edit" = "Drupal\state_form_entity\Form\StateFormEntityForm",
 *       "delete" = "Drupal\state_form_entity\Form\StateFormEntityDeleteForm"
 *     }
 *   },
 *   bundle_of = "state_form_entity",
 *   config_prefix = "state_form_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "name",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/state_form_entity/edit/{type}",
 *     "delete" = "/admin/structure/state_form_entity/delete/{type}",
 *   },
 *   config_export = {
 *     "name",
 *     "type",
 *     "description",
 *     "formFieldParent",
 *     "fieldTarget",
 *     "fieldToggle",
 *     "statesFormEntityTypeElement",
 *     "statesFormEntityType",
 *     "valueNested",
 *   }
 * )
 */
class StateFormEntity extends ConfigEntityBundleBase {

  /**
   * The field name toggle.
   *
   * @var string
   */
  public $name;

  /**
   * The entity type id.
   *
   * @var $entityTypeId
   */
  public $type;

  /**
   * A brief description of this node type.
   *
   * @var string
   */
  protected $description;

  /**
   * The form handle field.
   *
   * @var string
   */
  public $formFieldParent;

  /**
   * The field target.
   *
   * @var string
   */
  public $fieldTarget;

  /**
   * The field target.
   *
   * @var string
   */
  public $fieldToggle;

  /**
   * The different states behaviors.
   *
   * @var array
   */
  public $statesFormEntityTypeElement;

  /**
   * The different states behaviors.
   *
   * @var array
   */
  public $statesFormEntityType;

  /**
   * The ajax behaviors callback.
   *
   * @var array
   */
  public $valueNested;

  /**
   * @return mixed
   */
  public function id() {
    return $this->type;
  }

  /**
   * Get the name.
   *
   * @return mixed
   *   The name or null.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Set the name.
   *
   * @param mixed $name
   *   The name from form.
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getFormFieldParent() {
    return $this->formFieldParent;
  }

  /**
   * @param string $formFieldParent
   */
  public function setFormFieldParent($formFieldParent) {
    $this->formFieldParent = $formFieldParent;
  }

  /**
   * @return string
   */
  public function getFieldTarget() {
    return $this->fieldTarget;
  }

  /**
   * @param string $fieldTarget
   */
  public function setFieldTarget($fieldTarget) {
    $this->fieldTarget = $fieldTarget;
  }

  /**
   * @return string
   */
  public function getFieldToggle() {
    return $this->fieldToggle;
  }

  /**
   * @param string $fieldToggle
   */
  public function setFieldToggle($fieldToggle) {
    $this->fieldToggle = $fieldToggle;
  }

  /**
   * @return array
   */
  public function getStateFormEntityTypeElement() {
    return $this->statesFormEntityTypeElement;
  }

  /**
   * @param array $statesFormEntityTypeElement
   */
  public function setStatesFormEntityTypeElement($statesFormEntityTypeElement) {
    $this->statesFormEntityTypeElement = $statesFormEntityTypeElement;
  }

  /**
   * Get the states.
   *
   * @return mixed
   *   The states or null.
   */
  public function getStateFormEntityType() {
    return $this->statesFormEntityType;
  }

  /**
   * Set the states.
   *
   * @param mixed $statesFormEntityType
   *   The states from form.
   */
  public function setStatesFormEntityType($statesFormEntityType) {
    $this->statesFormEntityType = $statesFormEntityType;
  }

  /**
   * Get value to toggle.
   *
   * @return mixed
   *   Get field toggle.
   */
  public function getValueNested() {
    return $this->valueNested;
  }

  /**
   * Set the value field toggle.
   *
   * @param mixed $valueNested
   *   Set the value field toggle from form.
   */
  public function setValueNested($valueNested) {
    $this->valueNested = $valueNested;
  }

  /**
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @param string $description
   */
  public function setDescription($description) {
    $this->description = $description;
  }

}
