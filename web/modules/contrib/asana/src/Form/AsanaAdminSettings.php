<?php

namespace Drupal\asana\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AsanaAdminSettings.
 */
class AsanaAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'asana.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'asana_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('asana.settings');
    $form['personal_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personal Access Token'),
      '#description' => $this->t('To generate the token in Asana go to: My profile settings... » Apps » Manage Developer Apps » Personal Access Tokens » Create New Personal Access Token.'),
      '#maxlength' => 34,
      '#size' => 40,
      '#required' => TRUE,
      '#default_value' => $config->get('personal_access_token'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Saving the value.
    $this->config('asana.settings')
      ->set('personal_access_token', $form_state->getValue('personal_access_token'))
      ->save();
  }

}
