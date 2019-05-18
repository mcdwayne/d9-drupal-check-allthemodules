<?php

namespace Drupal\module_builder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for configuring Module Builder.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_builder_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'module_builder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('module_builder.settings');

    $form['data_directory'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Module builder data directory'),
      '#field_prefix' => 'public://',
      '#default_value' => $config->get('data_directory'),
      '#description' => $this->t("The location to store Module Builder's processed data within the site's files directory."),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('module_builder.settings')
      ->set('data_directory', $form_state->getValue('data_directory'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
