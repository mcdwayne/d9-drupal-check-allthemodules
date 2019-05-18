<?php

namespace Drupal\akismet\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\akismet\Controller\FormController;

/**
 * Defines the form entity.
 *
 * @ingroup akismet
 *
 * @ConfigEntityType(
 *   id = "akismet_form",
 *   label = @Translation("Akismet Form Configuration"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\akismet\Controller\FormListBuilder",
 *     "form" = {
 *       "add" = "Drupal\akismet\Form\FormAdd",
 *       "edit" = "Drupal\akismet\Form\FormEdit",
 *       "delete" = "Drupal\akismet\Form\FormDelete",
 *     },
 *   },
 *   admin_permission = "administer akismet",
 *   config_prefix = "form",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/akismet/form/{akismet_form}/edit",
 *     "delete-form" = "/admin/config/content/akismet/form/{akismet_form}/delete",
 *   }
 * )
 */
class Form extends ConfigEntityBase implements FormInterface {

  /**
   * The form ID.
   *
   * @var string
   */
  public $id;

  /**
   * The form UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The form label.
   *
   * @var string
   */
  public $label;

  /**
   * The form mode.
   *
   * @var string
   */
  public $mode;

  /**
   * The form fields to analyze.
   *
   * @var array
   */
  public $enabled_fields = array();

  /**
   * What to do if Akismet is not sure.
   *
   * @var string
   */
  public $unsure = 'moderate';

  /**
   * What to do if Akismet identified it as spam.
   *
   * @var boolean
   */
  public $discard = TRUE;

  /**
   * Stored mapping of the Drupal fields to Akismet fields.
   *
   * @var array
   */
  public $mapping = array();

  /**
   * The module that manages the protected form.
   *
   * @var string
   */
  public $module;

  /**
   * The entity of the protected form.
   *
   * @var string
   */
  public $entity;

  /**
   * The entity bundle of the protected form.
   *
   * @var string
   */
  public $bundle;

  /**
   * {@inheritDoc}
   *
   */
  public function initialize($form_id = NULL) {
    $akismet_form = get_object_vars($this);
    if (empty($form_id) && empty($this->id)) {
      return $akismet_form;
    }
    if ($this->isNew()) {
      $forms = FormController::getProtectableForms();
      if (empty($forms[$form_id])) {
        return $akismet_form;
      }
      $akismet_form += $forms[$form_id];
      $this->id = $form_id;
      $this->label = $forms[$form_id]['title'];
      foreach ($forms[$form_id] as $name => $value) {
        if (property_exists($this, $name)) {
          $this->{$name} = $value;
        }
      }
      $module = $this->module;
    }
    else {
      $form_id = $this->id();
      $module = $this->module;
      $forms = NULL;
    }
    // Add all of the configuration information defined in hooks.
    $form_details = FormController::getProtectedFormDetails($form_id, $module, $forms);
    if ($this->isNew()) {
      // Overwrite the element properties with form details when supplied.
      $akismet_form = array_merge($akismet_form, $form_details);
    }
    else {
      // The entity has already been configured so use it's data over the
      // configuration details.
      $akismet_form = array_merge($form_details, $akismet_form);
    }

    if ($this->isNew()) {
      $akismet_form['enabled_fields'] = array_keys($akismet_form['elements']);
      $this->setEnabledFields(array_keys($akismet_form['elements']));

      // Set the defaults
      foreach ($akismet_form as $field => $value) {
        if (property_exists($this, $field) && !empty($value)) {
          $this->{$field} = $value;
        }
      }
    }

    return $akismet_form;
  }

  /**
   * {@inheritDoc}
   */
  public function getEnabledFields() {
    return $this->enabled_fields;
  }

  /**
   * {@inheritDoc}
   */
  public function setEnabledFields(array $fields) {
    $this->enabled_fields = $fields;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getUnsure() {
    return $this->unsure;
  }

  /**
   * {@inheritDoc}
   */
  public function setUnsure($handling) {
    // @todo: Convert unsure handling values to constants.
    if (in_array($handling, array('moderate', 'discard'))) {
      $this->unsure = $handling;
    }
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getDiscard() {
    return $this->discard;
  }

  /**
   * {@inheritDoc}
   */
  public function setDiscard($discard) {
    $this->discard = $discard;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getMapping() {
    return $this->mapping;
  }

  /**
   * {@inheritDoc}
   */
  public function setMapping(array $mapping) {
    $this->mapping = $mapping;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * {@inheritDoc}
   */
  public function setModule($module) {
    $this->module = $module;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritDoc}
   */
  public function setEntity($entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritDoc}
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;
    return $this;
  }
}
