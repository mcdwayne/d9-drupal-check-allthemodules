<?php

namespace Drupal\visualn_dataset\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VisualNDataSetTypeForm.
 */
class VisualNDataSetTypeForm extends EntityForm {

  const VISUALN_RESOURCE_PROVIDER_FIELD_TYPE_ID = 'visualn_resource_provider';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $visualn_dataset_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_dataset_type->label(),
      '#description' => $this->t("Label for the VisualN Data Set type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_dataset_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn_dataset\Entity\VisualNDataSetType::load',
      ],
      '#disabled' => !$visualn_dataset_type->isNew(),
    ];

    // get the list of visualn_resource_provider fields attached to the entity type / bundle
    // also considered  base and bundle fields
    // see ContentEntityBase::bundleFieldDefinitions() and ::baseFieldDefinitions()
    $options = [];

    // @todo: instantiate on create
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $entity_type = $visualn_dataset_type->getEntityType()->getBundleOf();

    // for new drawing type bundle is empty
    $bundle = $visualn_dataset_type->id();
    $bundle_fields = $entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    // for new drawing types it may still contain base fields (e.g. "Default resource provider" field)
    // so do not skip them
    foreach ($bundle_fields as $field_name => $field_definition) {
      if ($field_definition->getType() == static::VISUALN_RESOURCE_PROVIDER_FIELD_TYPE_ID) {
        $options[$field_name] = $field_definition->getLabel();
      }
    }

    // sort options by name
    asort($options);

    // If entity type is new and visualn_resource_provider base (or bundle) fields found (see DataSet entity class)
    // use the first field (generally there is one "Default resource provider" base field) as default.
    reset($options);
    $default_resource_provider = $visualn_dataset_type->isNew() && !empty($options) ? key($options) : $this->entity->getResourceProviderField();
    $form['resource_provider_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Resource provider field'),
      '#options' => $options,
      '#default_value' => $default_resource_provider,
      '#description' => $this->t('The field that is used to provide resource object.'),
      '#disabled' => $visualn_dataset_type->isNew() && empty($options),
      '#empty_value' => '',
      '#empty_option' => t('- Select resource provider field -'),
      '#required' => !empty($options),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_dataset_type = $this->entity;
    $status = $visualn_dataset_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Data Set type.', [
          '%label' => $visualn_dataset_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Data Set type.', [
          '%label' => $visualn_dataset_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_dataset_type->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $resource_provider_field = $form_state->getValue('resource_provider_field') ?: '';
    $this->entity->set('resource_provider_field', $resource_provider_field);
  }

}
