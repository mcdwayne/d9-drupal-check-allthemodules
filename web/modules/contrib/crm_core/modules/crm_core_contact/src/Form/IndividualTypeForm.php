<?php

namespace Drupal\crm_core_contact\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for edit individual types.
 */
class IndividualTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\crm_core_contact\Entity\IndividualType $type */
    $type = $this->entity;

    $form['name'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->name,
      '#description' => $this->t('The human-readable name of this individual type. It is recommended that this name begin with a capital letter and contain only letters, numbers, and spaces. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 32,
    ];

    $form['type'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => 'Drupal\crm_core_contact\Entity\IndividualType::load',
        'source' => ['name'],
      ],
      '#description' => $this->t('A unique machine-readable name for this individual type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->description,
      '#description' => $this->t('Describe this individual type.'),
    ];

    // Primary fields section.
    $form['primary_fields_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Primary Fields'),
      '#description' => $this->t('Primary fields are used to tell other modules what fields to use for common communications tasks such as sending an email, addressing an envelope, etc. Use the fields below to indicate the primary fields for this individual type.'),
    ];

    // @todo Move primary fields array to some hook. This Would allow extend this
    // list to other modules. This hook should return arra('key'=>t('Name')).
    $default_primary_fields = ['email', 'address', 'phone'];
    // $primary_fields = variable_get('crm_core_contact_default_primary_fields', $default_primary_fields);.
    $primary_fields = $default_primary_fields;
    $options = [];
    if (isset($type->type)) {
      /* @var \Drupal\Core\Field\FieldDefinitionInterface[] $instances */
      $instances = \Drupal::service('entity_field.manager')->getFieldDefinitions('crm_core_individual', $type->type);
      $instances = isset($instances[$type->type]) ? $instances[$type->type] : [];
      foreach ($instances as $instance) {
        $options[$instance->getName()] = $instance->getLabel();
      }
    }
    foreach ($primary_fields as $primary_field) {
      $form['primary_fields_container'][$primary_field] = [
        '#type' => 'select',
        '#title' => $this->t('Primary @field field', ['@field' => $primary_field]),
        '#default_value' => empty($type->primary_fields[$primary_field]) ? '' : $type->primary_fields[$primary_field],
        '#empty_value' => '',
        '#empty_option' => $this->t('--Please Select--'),
        '#options' => $options,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save individual type');
    $actions['delete']['#title'] = $this->t('Delete individual type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('type', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;

    $status = $type->save();

    $t_args = ['%name' => $type->label(), 'link' => \Drupal::url('entity.crm_core_individual_type.collection')];

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The individual type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The individual type %name has been added.', $t_args));
      \Drupal::logger('crm_core_individual')->notice('Added individual type %name.', $t_args);
    }

    $form_state->setRedirect('entity.crm_core_individual_type.collection');
  }

}
