<?php

namespace Drupal\config_suite\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdminSettingsForm.
 *
 * @package Drupal\config_suite\Form
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'config_suite.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_suite_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('config_suite.settings');
    $form['automatic_import'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatic Import'),
      '#description' => $this->t('Automatically import changes when the sync folder has pending updates.'),
      '#default_value' => $config->get('automatic_import'),
    ];
    $form['automatic_export'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatic Export'),
      '#description' => $this->t('Automatically export configuration to the sync folder when you save a form.'),
      '#default_value' => $config->get('automatic_export'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('config_suite.settings')
      ->set('automatic_import', $form_state->getValue('automatic_import'))
      ->set('automatic_export', $form_state->getValue('automatic_export'))
      ->save();
  }

}
