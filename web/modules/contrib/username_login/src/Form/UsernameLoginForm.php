<?php

/**
 * @file
 * Contains \Drupal\username_login\Form\UsernameLoginForm.
 */

namespace Drupal\username_login\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the Email login form.
 */
class UsernameLoginForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'username_login_form_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#attributes' => [
        'placeholder' => $this->t('Username'),
        'class' => ['form-control'],
      ],
      '#size' => 50,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user = trim($form_state->getValue('name'));

    $username = user_load_by_name($user);

    if ($username == FALSE) {
      $form_state->setErrorByName('name', $this->t('Sorry, your account could not be found.'));
    }
    else {
      $username_status = $username->get('status')->value;
      if ($username_status != 1) {
        $form_state->setErrorByName('name', $this->t('Your account has not been activated or is blocked.'));
      }
    }	
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	$account = $this->currentUser(); 	
    $user = trim($form_state->getValue('name'));

    $username = user_load_by_name($user);
    $username_roles = $username->getRoles();
    $username_roles = array_combine($username_roles, $username_roles);

    if ($this->usernameLoginVerifyUserRoles($username_roles)) {
      $user = $username;
      user_login_finalize($user);
      drupal_set_message($this->t('You are now logged in as %user.', ['%user' => $account->getUsername()]));	  
    }
    else {
      drupal_set_message($this->t("Login failed."), 'error');
    }
  }

  /**
   * Function to verify user role for login via username.
   */
  public function usernameLoginVerifyUserRoles($user_roles) {
    $config = \Drupal::config('username_login.settings');
    $username_login_roles = $config->get('username_login_roles');

    if (is_array($username_login_roles)) {
      $username_login_roles = array_filter($username_login_roles);
    }

    unset($user_roles['authenticated']);

    if (count(array_diff_key($user_roles, $username_login_roles)) || empty($user_roles) || empty($username_login_roles)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

}
