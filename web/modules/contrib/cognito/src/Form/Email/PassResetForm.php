<?php

namespace Drupal\cognito\Form\Email;

use Drupal\cognito\Aws\CognitoInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Email;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The password reset form.
 */
class PassResetForm extends FormBase {

  /**
   * Track whether we're showing the confirmation form.
   *
   * @var bool
   */
  protected $showConfirmation = FALSE;

  /**
   * Keep track of values between form steps.
   *
   * @var array
   */
  protected $multistepFormValues = [];

  /**
   * The cognito service.
   *
   * @var \Drupal\cognito\Aws\Cognito
   */
  protected $cognito;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * PassResetForm constructor.
   *
   * @param \Drupal\cognito\Aws\CognitoInterface $cognito
   *   The cognito service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(CognitoInterface $cognito, LoggerInterface $logger) {
    $this->cognito = $cognito;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cognito.aws'),
      $container->get('logger.factory')->get('cognito')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cognito_email_password_reset';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->showConfirmation) {
      return $this->buildConfirmationForm($form);
    }

    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#size' => 60,
      '#maxlength' => Email::EMAIL_MAX_LENGTH,
      '#default_value' => $this->currentUser()->isAuthenticated() ? $this->currentUser()->getEmail() : '',
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Reset Password')];

    $form['#validate'][] = '::validateForgotPassword';
    return $form;
  }

  /**
   * Builds the confirmation step form.
   *
   * @param array $form
   *   The form we're adding to.
   *
   * @return array
   *   The form array.
   */
  protected function buildConfirmationForm(array $form) {
    $form['#title'] = $this->t('Please Check your email for the confirmation code');

    // Hide the email form.
    $form['mail']['#access'] = FALSE;

    $form['confirmation_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation code'),
      '#description' => $this->t('This code has been emailed to your provided email address.'),
    ];

    $form['new_password'] = [
      '#type' => 'password',
      '#title' => $this->t('New password'),
      '#description' => $this->t('Please enter your new password. It should be at least 8 characters and consist of 1 number, 1 uppercase letter and 1 lowercase letter.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
    ];

    $form['#validate'][] = '::validateConfirmation';
    return $form;
  }

  /**
   * Validation the forgot password request.
   */
  public function validateForgotPassword(array &$form, FormStateInterface $form_state) {
    $mail = strtolower(trim($form_state->getValue('mail')));
    $result = $this->cognito->forgotPassword($mail);

    $result->hasError() ? $form_state->setErrorByName(NULL, $result->getError()) : $this->showConfirmationStep($form_state);
  }

  /**
   * Validate the confirmation form.
   */
  public function validateConfirmation(array &$form, FormStateInterface $form_state) {
    $mail = strtolower($this->multistepFormValues['mail']);
    $result = $this->cognito->confirmForgotPassword($mail, $form_state->getValue('new_password'), $form_state->getValue('confirmation_code'));

    if ($result->hasError()) {
      $form_state->setErrorByName('confirmation_code', $result->getError());
    }
    else {
      $this->logger->notice('Password reset for %email.', ['%email' => $mail]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage($this->t('Your password has now been reset.'));
    $form_state->setRedirect('user.login');
  }

  /**
   * Show the confirmations step of the form.
   *
   * This method handles saving any submitted values and rebuilding the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function showConfirmationStep(FormStateInterface $form_state) {
    $this->showConfirmation = TRUE;
    $form_state->setRebuild();

    $this->multistepFormValues = $form_state->getValues() + $this->multistepFormValues;
  }

}
