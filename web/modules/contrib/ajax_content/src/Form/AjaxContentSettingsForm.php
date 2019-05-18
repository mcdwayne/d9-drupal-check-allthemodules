<?php

namespace Drupal\ajax_content\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class AjaxContentSettingsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajax_content_admin_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ajax_content.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ajax_content.settings');

    $form['ajax_content_load_container_selector'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Container selector to load ajax content.'),
      '#default_value' => $config->get('ajax_content_load_container_selector'),
    );

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      // Retrieve the configuration
      \Drupal::configFactory()->getEditable('ajax_content.settings')
      // Set the submitted configuration setting
      ->set('ajax_content_load_container_selector', $form_state->getValue('ajax_content_load_container_selector'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
