<?php

namespace Drupal\account_settings_tab\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Entity;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class AccountSettingsTabForm.
 *
 * @package Drupal\account_settings_tab\Form
 */
class AccountSettingsTabForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'account_settings_tab_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $current_path = explode('/', \Drupal::service('path.current')->getPath());
    $roles = \Drupal::currentUser()->getRoles();
    if(!in_array('administrator', $roles) && $current_path[2] != $user->id()) {
      throw new AccessDeniedHttpException();
    }
    $account = $this->currentUser();
    $user = $this->currentUser();
    $config = \Drupal::config('user.settings');
    $register = $account->isAnonymous();
    $admin = $user->hasPermission('administer users');
    $current_path = explode('/', \Drupal::service('path.current')->getPath());
    $user_default_data = User::load($current_path[2]);
    $form['account'] = array(
      '#type'   => 'container',
      '#weight' => -10,
    );
    $form['account']['mail'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email.'),
      '#required' => !(!$account->getEmail() && $user->hasPermission('administer users')),
      '#default_value' => (!$register ? $user_default_data->get('mail')->value : ''),
    );
    $form['account']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
      '#required' => TRUE,
      '#attributes' => array(
        'class' => array('username'),
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ),
      '#default_value' => (!$register ? $user_default_data->get('name')->value : ''),
      '#access' => ($register || ($user->id() == $account->id() && $user->hasPermission('change own username')) || $admin),
    );
    if (!$register) {
      $form['account']['pass'] = array(
        '#type' => 'password_confirm',
        '#size' => 25,
        '#description' => $this->t('To change the current user password, enter the new password in both fields.'),
      );
      if (!$form_state->get('user_pass_reset')) {
        $user_pass_reset = isset($_SESSION['pass_reset_' . $account->id()]) && Crypt::hashEquals($_SESSION['pass_reset_' . $account->id()], \Drupal::request()->query->get('pass-reset-token'));
        $form_state->set('user_pass_reset', $user_pass_reset);
      }
      if ($current_path[2] == $account->id()) {
        $form['account']['current_pass'] = array (
          '#type' => 'password',
          '#title' => $this->t('Current password'),
          '#size' => 25,
          '#access' => !$form_state->get('user_pass_reset'),
          '#weight' => -5,
          '#attributes' => array('autocomplete' => 'off'),
        );
        $form_state->set('user', $account);
        if (!$form_state->get('user_pass_reset')) {
          $form['account']['current_pass']['#description'] = $this->t('Required if you want to change the %mail or %pass below. <a href=":request_new_url" title="Send password reset instructions via email.">Reset your password</a>.', array(
            '%mail' => $form['account']['mail']['#title'],
            '%pass' => $this->t('Password'),
            ':request_new_url' => $this->url('user.pass'),
          ));
        }
      }
    }
    elseif (!$config->get('verify_mail') || $admin) {
      $form['account']['pass'] = array(
        '#type' => 'password_confirm',
        '#size' => 25,
        '#description' => $this->t('Provide a password for the new account in both fields.'),
        '#required' => TRUE,
      );
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Submit'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $current_path = explode('/', \Drupal::service('path.current')->getPath());
    $account = User::load($current_path[2]);
    //Validating while changing the username
    $name_default_value = $form['account']['name']['#default_value'];
    $name_value = $form['account']['name']['#value'];
      if ($name_default_value != $name_value) {
        $user_exist = user_load_by_name($name_value);
        if (!empty($user_exist)) {
          $form_state->setErrorByName('name', t("Username already exist! provide an other username."));

        }
      }
      // Validate changing the  email
    if ($current_path[2] == $user->id()) {
      $mail_default_value = $form['account']['mail']['#default_value'];
      $mail_value = $form['account']['mail']['#value'];
      $current_pass = $form['account']['current_pass']['#value'];
      $authorised = \Drupal::service('user.auth')
        ->authenticate($account->getAccountName(), $current_pass);
      if (empty($current_pass)) {
        $form_state->setErrorByName('current_pass', t("Enter current password."));
      }
      if (!empty($current_pass) && $mail_default_value != $mail_value) {
        if (!$authorised) {
          $form_state->setErrorByName('mail', t("Please enter the right password to change email.."));
        }
      }
      if (empty($current_pass) && $mail_default_value != $mail_value) {
        $form_state->setErrorByName('mail', t("Enter current password to change Email id."));
      }
      // set the password
      $pass1 = $form['account']['pass']['#value']['pass1'];
      $pass2 = $form['account']['pass']['#value']['pass2'];
      if (!empty($pass1) && !empty($pass2)) {
        if (!$authorised) {
          $form_state->setErrorByName('pass', t("Please enter the right password to change password."));
        }
      }
      if (empty($current_pass) && !empty($pass1) && !empty($pass2)) {
        $form_state->setErrorByName('pass', t("Enter current password to change password id."));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_path = explode('/', \Drupal::service('path.current')->getPath());
    $account = User::load($current_path[2]);
      // set the username
      $name_default_value = $form['account']['name']['#default_value'];
      $name_value = $form['account']['name']['#value'];
      if ($name_default_value != $name_value) {
        $user_exist = user_load_by_name($name_value);
        if (empty($user_exist)) {
          $account->setUsername($name_value);
          drupal_set_message("Username successfully updated..", 'status');
          $account->save();
        }
      }
      // set the email
      $mail_default_value = $form['account']['mail']['#default_value'];
      $mail_value = $form['account']['mail']['#value'];
    if ($mail_default_value != $mail_value) {
          $mail_value = $form['account']['mail']['#value'];
          $account->setEmail($mail_value);
          drupal_set_message("User email successfully updated..", 'status');
          $account->save();
      }
      // set the password
      $pass1 = $form['account']['pass']['#value']['pass1'];
      $pass2 = $form['account']['pass']['#value']['pass2'];
      if (!empty($pass1) && !empty($pass2)) {
          $account->setPassword($pass1);
          drupal_set_message("Password successfully updated..", 'status');
          $account->save();
      }
    }
}
