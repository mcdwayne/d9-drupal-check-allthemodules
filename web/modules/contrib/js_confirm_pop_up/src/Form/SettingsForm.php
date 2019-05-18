<?php

namespace Drupal\js_confirm_pop_up\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'js_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'js_confirm_pop_up.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('js_confirm_pop_up.settings');
    $form['js_confirm_pop_up_actual_form_id'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Actual drupal form id:'),
      '#default_value' => $config->get('js_confirm_pop_up_id'),
      '#description' => t('Enter comma(,) separated form ids of drupal form(Machine name) .ie node_page_form'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('js_confirm_pop_up.settings');
    $config->set('js_confirm_pop_up_id', $form_state->getValue('js_confirm_pop_up_actual_form_id'))
        ->save();

    parent::submitForm($form, $form_state);
  }
}
