<?php

namespace Drupal\forgot_username\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for /user/username.
 */
class ForgotUsernameForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email_address'] = [
      '#type' => 'textfield',
      '#title' => t('Your e-mail address'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Request Username'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user.username';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableNames() {
    return 'forgot_username';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email_address');
    $user = \Drupal::database()->select('users_field_data', 'u')->fields('u', ['name'])->condition('mail', $email)->fetchField();
    if (!\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setErrorByName("email_address", "The e-mail address is not valid.");
    }
    if (!$user) {
      $form_state->setErrorByName("email_address", "There is no account with that e-mail address.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $email = $form_state->getValue('email_address');
    \Drupal::state()->set('forgot_username_mail', $email);
    \Drupal::service('plugin.manager.mail')->mail('forgot_username', 'notice', $email, $language, $params);
    \Drupal::messenger()->addMessage($this->t('Your username has been e-mailed to the address specified. The e-mail might end up in spam folder, so please check there before complaining.'));
  }

}
