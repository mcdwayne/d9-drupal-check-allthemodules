<?php

namespace Drupal\crm_core_contact\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OrganizationTypeForm.
 *
 * Provides a form for the OrganizationType entity.
 *
 * @package Drupal\crm_core_contact\Form
 */
class OrganizationTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\crm_core_contact\Entity\OrganizationType $type */
    $type = $this->entity;

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => $this->t('The human-readable name of this organization type. It is recommended that this name begin with a capital letter and contain only letters, numbers, and spaces. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 32,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => 'Drupal\crm_core_contact\Entity\OrganizationType::load',
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this organization type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => $this->t('Describe this organization type.'),
    ];

    // Primary fields section.
    $form['primary_fields_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Primary Fields'),
      '#description' => $this->t('Primary fields are used to tell other modules what fields to use for common communications tasks such as sending an email, addressing an envelope, etc. Use the fields below to indicate the primary fields for this organization type.'),
    ];

    // @todo Move primary fields array to some hook. This Would allow extend this
    // list to other modules. This hook should return array('key'=>t('Name')).
    $primary_fields = ['email', 'address', 'phone'];
    $options = [];
    $type_id = $type->id();
    if (isset($type_id)) {
      /* @var \Drupal\Core\Field\FieldDefinitionInterface[] $instances */
      $instances = \Drupal::service('entity_field.manager')->getFieldDefinitions('crm_core_organization', $type_id);
      $instances = isset($instances[$type_id]) ? $instances[$type_id] : [];
      foreach ($instances as $instance) {
        $options[$instance->getName()] = $instance->getLabel();
      }
    }
    foreach ($primary_fields as $primary_field) {
      $form['primary_fields_container'][$primary_field] = [
        '#type' => 'select',
        '#title' => $this->t('Primary @field field', ['@field' => $primary_field]),
        '#default_value' => empty($type->getPrimaryFields()[$primary_field]) ? '' : $type->getPrimaryFields()[$primary_field],
        '#empty_value' => '',
        '#empty_option' => $this->t('--Please Select--'),
        '#options' => $options,
      ];
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save organization type');
    $actions['delete']['#title'] = $this->t('Delete organization type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $id = trim($form_state->getValue('id'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;

    $status = $type->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The organization type %name has been updated.', ['%name' => $type->label()]));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The organization type %name has been added.', ['%name' => $type->label()]));
      \Drupal::logger('crm_core_organization')->notice('Added organization type %name.', ['%name' => $type->label()]);
    }

    $form_state->setRedirect('entity.crm_core_organization_type.collection');
  }

}
