<?php

namespace Drupal\caps_lock_warning\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CustomForm.
 *
 * @package Drupal\caps_lock_warning\Form
 */
class CapLockConfigrationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'message.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'caps_lock_warning_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('message.adminsettings');

    $form['warning_name'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Warning Message:'),
      '#default_value' => $config->get('warning_name'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Save form.
    $this->config('message.adminsettings')
      ->set('warning_name', $form_state->getValue('warning_name'))
      ->save();
    // Display result.
    $config = $this->config('message.adminsettings');
    drupal_set_message($config->get('warning_name'));
  }

}
