<?php

namespace Drupal\ga_push\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * GA Push Settings Form.
 */
class GAPushSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ga_push_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ga_push.settings');

    $elements = ga_push_get_methods_option_list(NULL, FALSE);

    $form['ga_push_default_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default method'),
      '#options' => $elements,
      '#default_value' => $config->get('default_method'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ga_push.settings')
      ->set('default_method', $form_state->getValue('ga_push_default_method'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ga_push.settings'];
  }

}
