<?php

namespace Drupal\breadcrumb_extra_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * BreadcrumbExtraFieldSettingsForm Class.
 */
class BreadcrumbExtraFieldSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'breadcrumb_extra_field_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('breadcrumb_extra_field.settings');

    $entity_info = \Drupal::entityManager()->getDefinitions();
    $admin = $config->get(BREADCRUMB_EXTRA_FIELD_ADMIN);
    $allowed_entity_types = unserialize(BREADCRUMB_EXTRA_FIELD_ALLOWED_ENTITY_TYPES);

    $form[BREADCRUMB_EXTRA_FIELD_ADMIN] = [
      '#type' => 'fieldset',
      '#title' => t('Select entity types which are going to use the breadcrumb extra field'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#description' => t('Enable extra field for these entity types and bundles.'),
    ];

    foreach ($entity_info as $entity_type_key => $entity_type) {
      $bundle_options = [];

      // Skip not allowed entity types.
      if (in_array($entity_type_key, $allowed_entity_types)) {
        $bundles = \Drupal::entityManager()->getBundleInfo($entity_type_key);
        foreach ($bundles as $bundle_key => $bundle) {
          $bundle_options[$bundle_key] = $bundle['label'];
        }

        $form[BREADCRUMB_EXTRA_FIELD_ADMIN][$entity_type_key] = [
          '#type' => 'checkboxes',
          '#title' => $entity_type->getLabel(),
          '#options' => $bundle_options,
          '#default_value' => !empty($admin[$entity_type_key]) ?
          array_keys(array_filter($admin[$entity_type_key])) : [],
        ];
      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('breadcrumb_extra_field.settings')
      ->set(BREADCRUMB_EXTRA_FIELD_ADMIN, $form_state->getValue(BREADCRUMB_EXTRA_FIELD_ADMIN))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['breadcrumb_extra_field.settings'];
  }

}
