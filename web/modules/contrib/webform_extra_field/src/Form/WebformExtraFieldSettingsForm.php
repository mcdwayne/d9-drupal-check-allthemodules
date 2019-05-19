<?php

namespace Drupal\webform_extra_field\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * BreadcrumbExtraFieldSettingsForm Class.
 */
class WebformExtraFieldSettingsForm extends ConfigFormBase {

  const EXTRA_FIELD_ADMIN = 'webform_extra_field_admin';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_extra_field_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform_extra_field.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform_extra_field.settings');

    $entity_info = \Drupal::entityManager()->getDefinitions();
    $admin = $config->get(self::EXTRA_FIELD_ADMIN);

    $form[self::EXTRA_FIELD_ADMIN] = [
      '#type' => 'fieldset',
      '#title' => t('Select entity types which are going to use the webform extra field'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#description' => t('Enable extra field for these entity types and bundles.'),
    ];

    foreach ($entity_info as $entity_type_key => $entity_type) {
      // Restrict to fieldable content entities.
      if (
        !$entity_type instanceof ContentEntityType
        || is_null($entity_type->get('field_ui_base_route'))
      ) {
        continue;
      }
      $bundle_options = [];

      $bundles = \Drupal::entityManager()->getBundleInfo($entity_type_key);
      foreach ($bundles as $bundle_key => $bundle) {
        $bundle_options[$bundle_key] = $bundle['label'];
      }

      $form[self::EXTRA_FIELD_ADMIN][$entity_type_key] = [
        '#type' => 'checkboxes',
        '#title' => $entity_type->getLabel(),
        '#options' => $bundle_options,
        '#default_value' => !empty($admin[$entity_type_key]) ?
        array_keys(array_filter($admin[$entity_type_key])) : [],
      ];

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('webform_extra_field.settings')
      ->set(self::EXTRA_FIELD_ADMIN, $form_state->getValue(self::EXTRA_FIELD_ADMIN))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
