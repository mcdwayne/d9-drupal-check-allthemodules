<?php

/**
 * @file
 * Contains \Drupal\login_shadowbox\Form\LoginShadowboxSettingsForm.
 */

namespace Drupal\login_shadowbox\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure the Shadowbox login settings.
 */
class LoginShadowboxSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_shadowbox_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('login_shadowbox.settings');

    $form['login_shadowbox_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable shadowbox login'),
      '#description' => t('Enable this option if you want to be able to open your login form in shadowbox.'),
      '#default_value' => $config->get('login_shadowbox_enabled'),
    );

    // Login Dimensions.
    $form['login_shadowbox_login'] = array(
      '#type' => 'details',
      '#title' => t('Login Dimensions'),
      '#collapsed' => TRUE,
    );
    $form['login_shadowbox_login']['login_shadowbox_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Shadowbox login width'),
      '#description' => t('The width (in pixels) of shadowbox login form when it appears on screen.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('login_shadowbox_width'),
    );
    $form['login_shadowbox_login']['login_shadowbox_height'] = array(
      '#type' => 'textfield',
      '#title' => t('Shadowbox login height'),
      '#description' => t('The height (in pixels) of shadowbox login form when it appears on screen.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('login_shadowbox_height'),
    );

    // Registration Dimensions.
    $form['login_shadowbox_register'] = array(
      '#type' => 'details',
      '#title' => t('Registration Dimensions'),
      '#collapsed' => TRUE,
    );
    $form['login_shadowbox_register']['login_shadowbox_register_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Shadowbox registration width'),
      '#description' => t('The width (in pixels) of shadowbox containing the registration form when it appears on screen.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('login_shadowbox_register_width'),
    );
    $form['login_shadowbox_register']['login_shadowbox_register_height'] = array(
      '#type' => 'textfield',
      '#title' => t('Shadowbox registration height'),
      '#description' => t('The height (in pixels) of shadowbox containing the registration form when it appears on screen.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('login_shadowbox_register_height'),
    );

    // Reset Password Dimensions.
    $form['login_shadowbox_password'] = array(
      '#type' => 'details',
      '#title' => t('Reset Password Dimensions'),
      '#collapsed' => TRUE,
    );
    $form['login_shadowbox_password']['login_shadowbox_password_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Shadowbox reset password width'),
      '#description' => t('The width (in pixels) of shadowbox containing the reset password form when it appears on screen.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('login_shadowbox_password_width'),
    );
    $form['login_shadowbox_password']['login_shadowbox_password_height'] = array(
      '#type' => 'textfield',
      '#title' => t('Shadowbox reset password height'),
      '#description' => t('The height (in pixels) of shadowbox containing the reset password form when it appears on screen.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('login_shadowbox_password_height'),
    );

    $form['login_shadowbox_modal'] = array(
      '#type' => 'checkbox',
      '#title' => t('Shadowbox modal'),
      '#description' => t('Check this box to prevent mouse clicks on the overlay from closing Shadowbox.'),
      '#default_value' => $config->get('login_shadowbox_modal'),
    );

    $form['login_shadowbox_css'] = array(
      '#type' => 'textfield',
      '#title' => t('Shadowbox login css file'),
      '#description' => t('The css file to stylize the shadowbox login dialog.'),
      '#size' => 60,
      '#maxlength' => 255,
      '#default_value' => $config->get('login_shadowbox_css'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);

    $width = $form_state['values']['login_shadowbox_width'];
    $height = $form_state['values']['login_shadowbox_height'];

    if (!is_numeric($width) || $width < 0) {
      $this->setFormError('login_shadowbox_width', $form_state, $this->t('You must enter a positive number.'));
    }

    if (!is_numeric($height) || $height < 0) {
      $this->setFormError('login_shadowbox_height', $form_state, $this->t('You must enter a positive number.'));
    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    $this->config('login_shadowbox.settings')
      ->set('login_shadowbox_enabled', $form_state['values']['login_shadowbox_enabled'])
      ->set('login_shadowbox_width', $form_state['values']['login_shadowbox_width'])
      ->set('login_shadowbox_height', $form_state['values']['login_shadowbox_height'])
      ->set('login_shadowbox_register_width', $form_state['values']['login_shadowbox_register_width'])
      ->set('login_shadowbox_register_height', $form_state['values']['login_shadowbox_register_height'])
      ->set('login_shadowbox_password_width', $form_state['values']['login_shadowbox_password_width'])
      ->set('login_shadowbox_password_height', $form_state['values']['login_shadowbox_password_height'])
      ->set('login_shadowbox_modal', $form_state['values']['login_shadowbox_modal'])
      ->set('login_shadowbox_css', $form_state['values']['login_shadowbox_css'])
      ->save();
  }
}