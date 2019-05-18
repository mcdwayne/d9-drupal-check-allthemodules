<?php

/**
 * @file
 * Contains Drupal\prlp\Form\AdminSettingsForm.
 */

namespace Drupal\prlp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdminSettingsForm.
 *
 * @package Drupal\prlp\Form
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'prlp.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prlp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('prlp.settings');
    $form['password_required'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Password Entry Required'),
      '#description' => $this->t('If set, users will be required to enter a new password when they use a password reset link to login'),
      '#default_value' => $config->get('password_required'),
    );
    $form['login_destination'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Login Destination'),
      '#description' => $this->t('User will be taken to this path after they log in with the password reset link. Token %user can be used in the path, and will be replaced with the uid of the current user. Use %front for site front-page.'),
      // '#maxlength' => 64,
      // '#size' => 64,
      '#default_value' => $config->get('login_destination'),
    );
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

    $this->config('prlp.settings')
      ->set('password_required', $form_state->getValue('password_required'))
      ->set('login_destination', $form_state->getValue('login_destination'))
      ->save();
  }

}
