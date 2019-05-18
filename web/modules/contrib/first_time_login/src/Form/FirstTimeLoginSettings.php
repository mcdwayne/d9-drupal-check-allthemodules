<?php

namespace Drupal\first_time_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FirstTimeLoginSettings.
 *
 * @package Drupal\first_time_login\Form\FirstTimeLoginSettings
 */
class FirstTimeLoginSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'first_time_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'first_time_login.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    $config = \Drupal::configFactory()->getEditable('first_time_login.settings');
    $form = [];

    $form['first_time_login_config_days'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Enter the number of days.'),
      '#required' => TRUE,
      '#default_value' => $config->get('first_time_login_config_days'),
      '#description' => $this->t('Numeric field - enter number of days.
                        For example: Enter 60 for 60 days / 2 Months. </br>
                        User who has not updated their account since the above mentioned days,
                        will be prompted to updated their account after login.'),
    ];
    $form['first_time_login_new_user_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the welcome message for the newly registered users.'),
      '#required' => TRUE,
      '#default_value' => $config->get('first_time_login_new_user_message'),
      '#description' => $this->t('Text field - enter message.
                        For example: Welcome to the @site_name. </br>
                        Allowed tokens: @user, @site_name and @created_date'),
    ];
    $form['first_time_login_update_user_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the reminder message for the users </br>who have not
      updated their profile since the above configured number of days.'),
      '#required' => TRUE,
      '#default_value' => $config->get('first_time_login_update_user_message'),
      '#description' => $this->t('Text field - enter message.
                        For example: @user, please update your account. </br>
                        Allowed tokens: @user, @site_name and @updated_date'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('first_time_login_config_days'))) {
      $form_state->setErrorByName('first_time_login_config_days', t('Enter the number of days should be number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('first_time_login.settings')
      ->set('first_time_login_config_days', $form_state->getValue('first_time_login_config_days'))
      ->set('first_time_login_new_user_message', $form_state->getValue('first_time_login_new_user_message'))
      ->set('first_time_login_update_user_message', $form_state->getValue('first_time_login_update_user_message'))
      ->save();
    return parent::submitForm($form, $form_state);
  }

}
