<?php

/**
 * @file
 * Contains Drupal\username_login\Form\UsernameLoginAdminSettingsForm.
 */

namespace Drupal\username_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Username Login settings.
 */
class UsernameLoginAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheridoc}
   */
  public function defaultConfiguration() {
    $default_config = \Drupal::config('username_login.settings');
    return [
      'username_login_roles' => $default_config->get('username_login_roles'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'username_login_form_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['username_login.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')
      ->getEditable('username_login.settings');

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General Configurations'),
      '#open' => TRUE,
    ];	
	
    $username_login_roles = user_role_names(TRUE);
    unset($username_login_roles['authenticated'], $username_login_roles['administrator']);

    if (count($username_login_roles) == 0) {
      $form['general']['no_user_roles'] = [
        '#title' => $this->t('No user roles'),
        '#type' => 'fieldset',
      ];
      $form['general']['no_user_roles']['message'] = [
        '#markup' => $this->t('Currently there are no user roles in the system.'),
      ];
      return $form;
    }
    else {
      $form['general']['username_login_roles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select Roles'),
        '#description' => $this->t('Users having only selected role can login with just username'),
        '#default_value' => $config->get('username_login_roles'),
        '#options' => $username_login_roles,
      ];
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
    $config = $this->config('username_login.settings');
	$config
      ->set('username_login_roles', $form_state->getValue('username_login_roles'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
