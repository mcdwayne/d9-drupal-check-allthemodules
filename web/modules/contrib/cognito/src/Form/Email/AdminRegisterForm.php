<?php

namespace Drupal\cognito\Form\Email;

use Drupal\cognito\Form\CognitoAccountForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Cognito admin registration form.
 */
class AdminRegisterForm extends CognitoAccountForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cognito_admin_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    unset($form['account']['name']);
    unset($form['account']['pass']);

    $form['account']['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password.'),
      '#required' => TRUE,
      '#default_value' => '',
    ];

    $form['#validate'][] = '::validateRegistration';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $actions = parent::actionsElement($form, $form_state);
    $actions['submit']['#value'] = $this->t('Register');
    return $actions;
  }

  /**
   * Attempts to sign the user up against Cognito.
   */
  public function validateRegistration(array &$form, FormStateInterface $form_state) {
    $email = strtolower($form_state->getValue('mail'));

    $cognitoResult = $this->cognito->adminSignup($email, $email);
    if ($cognitoResult->hasError()) {
      $form_state->setErrorByName(NULL, $cognitoResult->getError());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage($this->t('The account has been created and the user has been sent a temporary password to login.'));

    $mail = strtolower($form_state->getValue('mail'));
    $this->externalAuth->register($mail, 'cognito', [
      'name' => $mail,
    ] + $form_state->getValues(), ['admin_registration' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Never run the entity validation because they block our forms. We could
    // remove the entity constraints on the user entity itself for username
    // and password. Those are now handled by Cognito.
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Never save the entity because that is handled by the externalauth
    // module.
  }

}
