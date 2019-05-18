<?php

/**
 * @file
 * Contains \Drupal\email_login\Form\EmailLoginForm.
 */

namespace Drupal\email_login\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the Email login form.
 */
class EmailLoginForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#attributes' => array('placeholder' => $this->t('Email address'), 'class' => array('form-control')),
      '#size' => 50,
      '#required' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = trim($form_state->getValue('email'));

    $email_user = user_load_by_mail($email);

    if ($email_user == FALSE) {
      $form_state->setErrorByName('email', $this->t('Sorry, unrecognized email address.'));
    }
    else {
      $email_user_status = $email_user->get('status')->value;
      if ($email_user_status != 1) {
        $form_state->setErrorByName('email', $this->t('Your account has not been activated or is blocked.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = trim($form_state->getValue('email'));

    $email_user = user_load_by_mail($email);
    $email_user_roles = $email_user->getRoles();
    $email_user_roles = array_combine($email_user_roles, $email_user_roles);

    if ($this->emailLoginVerifyUserRoles($email_user_roles)) {
      $user = $email_user;
      user_login_finalize($user);
      drupal_set_message($this->t('You are now logged in.'));
    }
    else {
      drupal_set_message($this->t("Login failed."), 'error');
    }
  }

  /**
   * Function to verify user role for login via email.
   */
  public function emailLoginVerifyUserRoles($user_roles) {

    $config = \Drupal::config('email_login.settings');
    $email_login_roles = $config->get('email_login_roles');

    if (is_array($email_login_roles)) {
      $email_login_roles = array_filter($email_login_roles);
    }

    unset($user_roles['authenticated']);

    if (count(array_diff_key($user_roles, $email_login_roles)) || empty($user_roles) || empty($email_login_roles)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

}
