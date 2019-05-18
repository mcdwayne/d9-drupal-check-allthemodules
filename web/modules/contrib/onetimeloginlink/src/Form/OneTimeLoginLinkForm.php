<?php

namespace Drupal\onetime_loginlink\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OneTimeLoginLinkForm.
 *
 * @package Drupal\onetime_loginlink\Form
 */
class OneTimeLoginLinkForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onetime_loginlink_form';
  }

  /**
   * To Create one time link for login.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['user_email_name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t('Enter User Name or User Email'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user_email_name_id = $form_state->getValue('user_email_name');
    if (preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/si', $user_email_name_id)) {
      $account = user_load_by_mail($user_email_name_id);
    }
    else {
      $account = user_load_by_name($user_email_name_id);
    }
    if(empty($account))
    {
      $form_state->setErrorByName('user_email_name', $this->t('Invalid User!!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_email_name = $form_state->getValue('user_email_name');
    if (preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/si', $user_email_name)) {
      $user_account = user_load_by_mail($user_email_name);
    }
    else {
      $user_account = user_load_by_name($user_email_name);
    }

    $login_url = user_pass_reset_url($user_account);

    if ($login_url) {
      drupal_set_message('OneTime LoginLink for '.$user_email_name);
      drupal_set_message($login_url);
    }
  }

}
