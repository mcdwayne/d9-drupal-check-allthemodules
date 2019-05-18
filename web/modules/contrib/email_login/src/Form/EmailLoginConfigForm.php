<?php

/**
 * @file
 * Contains Drupal\email_login\Form\EmailLoginConfigForm.
 */

namespace Drupal\email_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds module configuration form.
 */
class EmailLoginConfigForm extends ConfigFormBase {

  /**
   * {@inheridoc}
   */
  public function defaultConfiguration() {
    $default_config = \Drupal::config('email_login.settings');
    return array(
      'email_login_roles' => $default_config->get('email_login_roles'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'email_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_login_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('email_login.settings');

    $email_login_roles = user_role_names(TRUE);
    unset($email_login_roles['authenticated'], $email_login_roles['administrator']);

    if (count($email_login_roles) == 0) {
      $form['no_user_roles'] = array(
        '#title' => $this->t('No user roles'),
        '#type' => 'fieldset',
      );
      $form['no_user_roles']['message'] = array(
        '#markup' => $this->t('Currently there are no user roles in the system.'),
      );
      return $form;
    }
    else {
      $form['email_login_roles'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Select Roles'),
        '#description' => $this->t('Users having only selected role can login with just email address'),
        '#default_value' => $config->get('email_login_roles'),
        '#options' => $email_login_roles,
      );

      return parent::buildForm($form, $form_state);
    }

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
    $this->config('email_login.settings')
      ->set('email_login_roles', $form_state->getValue('email_login_roles'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
