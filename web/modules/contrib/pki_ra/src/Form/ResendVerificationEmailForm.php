<?php

namespace Drupal\pki_ra\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pki_ra\Processors\PKIRARegistrationProcessor;
use Drupal\Core\Url;

/**
 * Send a verification link to email address.
 */
class ResendVerificationEmailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pki_ra_resend_verfication_email';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => $this->t('To resend a verification link to your email address please enter your email address and submit.'),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Verification Email'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email_address = $form_state->getValue('email');
    if (!\Drupal::service('email.validator')->isValid($email_address, TRUE, TRUE)) {
      $form_state->setErrorByName('email', $this->t('The entered e-mail address %email is not valid.', [
        '%email' => $email_address,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email_address = $form_state->getValue('email');
    $registration = PKIRARegistrationProcessor::getregistrationByTitle($email_address);
    if (!empty($registration)) {
      $processor = new PKIRARegistrationProcessor($registration);
      // If there is a registration but not verified.
      if (empty($processor->isConfirmed($registration))) {
        $processor->sendEmailVerification();
      }
      else {
        // If the registration is alr neady verified.
        $login_url = Url::fromRoute('user.login')->toString();
        drupal_set_message($this->t('This email address is already verified. Please <a href=":login-url">login</a> here.',
          [':login-url' => $login_url]));
      }
    }
    else {
      // When there is no registration attached with this email address.
      $start_url = Url::fromRoute('node.add', ['node_type' => PKIRARegistrationProcessor::NODE_TYPE])->toString();
      drupal_set_message($this->t('Registration with this email address not found. Please register <a href=":registration-start">here</a>.',
        [':registration-start' => $start_url]), 'error');
    }
  }

}
