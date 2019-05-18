<?php

/**
 * @file
 * Contains \Drupal\owasp_login_velocity_check\Form\OwaspLoginVelocityForm.
 */

namespace Drupal\owasp_login_velocity_check\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class OwaspLoginVelocityForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'owasp_login_velocity_check_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor
    $form = parent::buildForm($form, $form_state);
    // Default settings
    $config = $this->config('owasp_login_velocity_check.settings');

    $form['check_time'] = array (
      '#type' => 'textfield',
      '#title' => t('OWASP login velocity check time interval (minutes)'),
      '#size' => 60,
      '#default_value' => $config->get('owasp_login_velocity_check.check_time'),
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $form['check_errormsg'] = array (
      '#type' => 'textarea',
      '#title' => t('OWASP login velocity check error message'),
      '#rows' => 5,
      '#default_value' => $config->get('owasp_login_velocity_check.check_errormsg'),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('owasp_login_velocity_check.settings');
    $config->set('owasp_login_velocity_check.check_time', $form_state->getValue('check_time'));
    $config->set('owasp_login_velocity_check.check_errormsg', $form_state->getValue('check_errormsg'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  protected function getEditableConfigNames() {
    return [
      'owasp_login_velocity_check.settings',
    ];
  }

}

