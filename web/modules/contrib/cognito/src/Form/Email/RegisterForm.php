<?php

namespace Drupal\cognito\Form\Email;

use Drupal\cognito\Form\CognitoAccountForm;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Cognito email registration form.
 */
class RegisterForm extends CognitoAccountForm {

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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cognito_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    unset($form['account']['name']);

    if ($this->showConfirmation) {
      return $this->buildConfirmationForm($form);
    }

    $form['account']['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
      '#default_value' => '',
      '#attributes' => ['placeholder' => $this->t('you@example.com')],
    ];

    $form['account']['pass'] = [
      '#type' => 'password_confirm',
      '#size' => 25,
      '#after_build' => ['::afterBuildPass'],
    ];

    $form['#validate'][] = '::validateRegistration';

    return $form;
  }

  /**
   * Add our description to the password field.
   *
   * @param array $element
   *   The password element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The altered element.
   */
  public function afterBuildPass(array $element, FormStateInterface $form_state) {
    $element['pass1']['#description'] = $this->t('Your password must contain 1 uppercase and 1 lowercase letter plus a number. Minimum length: 8 characters.');
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $actions = parent::actionsElement($form, $form_state);
    $actions['submit']['#value'] = $this->showConfirmation ? $this->t('Confirm') : $this->t('Register');
    return $actions;
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
    // We must hide all other field API fields for our confirmation form.
    foreach (Element::children($form) as $key) {
      // We must unset rather than deny access because we don't want the fields
      // to be validated.
      unset($form[$key]);
    }

    $form['#title'] = $this->t('Please check your email for the confirmation code');

    $form['confirmation_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation code'),
      '#description' => $this->t('This code has been emailed to your provided email address.'),
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
   * Attempts to sign the user up against Cognito.
   */
  public function validateRegistration(array &$form, FormStateInterface $form_state) {
    $email = strtolower($form_state->getValue('mail'));
    $password = trim($form_state->getValue('pass'));

    // If there were any errors up to this point then do nothing.
    if ($form_state->hasAnyErrors()) {
      return FALSE;
    }

    $result = $this->cognito->signUp($email, $password, $email);
    if ($result->hasError()) {
      // If the user already exists and they're attempting to submit the
      // registration form, then maybe something went wrong with their
      // confirmation. Resend it and then let them try again.
      if ($result->getErrorCode() === 'UsernameExistsException') {
        $this->attemptResend($email, $form_state);
      }
      else {
        $form_state->setErrorByName(NULL, $result->getError());
      }
    }
    else {
      // Register the user but they won't be confirmed.
      $values = $form_state->getValues();
      unset($values['pass']);
      $this->entity = $this->externalAuth->register($email, 'cognito', [
        'name' => $email,
      ] + $values, ['frontend_registration' => TRUE]);
    }
  }

  /**
   * Validate the confirmation form.
   */
  public function validateConfirmation(array &$form, FormStateInterface $form_state) {
    $email = strtolower($this->multistepFormValues['mail']);
    $result = $this->cognito->confirmSignup($email, trim($form_state->getValue('confirmation_code')));

    if ($result->hasError()) {
      $form_state->setErrorByName('confirmation_code', $result->getError());
    }
  }

  /**
   * Attempt to resend the confirmation code for this users email.
   *
   * @param string $email
   *   The users email address.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function attemptResend($email, FormStateInterface $form_state) {
    $result = $this->cognito->resendConfirmationCode($email);

    if ($result->hasError()) {
      // If we had any kind of error resending the confirmation code then we
      // fall back to the original user did not exist error.
      $form_state->setErrorByName(NULL, new FormattableMarkup($this->cognitoMessages->userAlreadyExistsRegister(), []));
    }
    else {
      $this->messenger()->addMessage($this->cognitoMessages->confirmationResent(), 'warning');

      if (!$this->usesClickToConfirm()) {
        $this->showConfirmationStep($form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->usesClickToConfirm()) {
      if ($message = $this->cognitoMessages->clickToConfirm()) {
        $this->messenger()->addMessage($message);
      }

      $event = new GenericEvent(NULL, ['url' => Url::fromRoute('<front>')]);
      $this->eventDispatcher->dispatch('cognito.registered_click_to_confirm', $event);
      $form_state->setRedirectUrl($event->getArgument('url'));
    }
    elseif ($this->usesAutoConfirm() || $this->showConfirmation) {
      $this->messenger()->addMessage($this->cognitoMessages->registrationConfirmed());
      $mail = $this->usesAutoConfirm() ? $form_state->getValue('mail') : $this->multistepFormValues['mail'];
      $mail = strtolower($mail);

      $this->externalAuth->login($mail, 'cognito');
      $event = new GenericEvent(NULL, ['url' => Url::fromRoute('<front>')]);
      $this->eventDispatcher->dispatch('cognito.registered_logged_in', $event);
      $form_state->setRedirectUrl($event->getArgument('url'));
    }
    else {
      $this->messenger()->addMessage($this->cognitoMessages->registrationComplete());
      $this->showConfirmationStep($form_state);
    }
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

    // Never save the password.
    unset($this->multistepFormValues['pass']);
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

  /**
   * Checks if we are using click to confirm.
   *
   * This is an advanced option that can be used if you don't want to use the
   * confirmation step in the form and instead want the user to click a link
   * in their emails to confirm their account. This does not happen by default
   * and will require you to setup a Lambda function to send the confirmation
   * code to the user.
   *
   * @return bool
   *   TRUE if we are using click to confirm otherwise FALSE.
   *
   * @see \Drupal\cognito\Controller\ConfirmationController
   */
  protected function usesClickToConfirm() {
    return (bool) $this->config('cognito.settings')->get('click_to_confirm_enabled');
  }


  /**
   * Checks if we are using auto confirm.
   *
   * This options allows users to register without having to validate their
   * email or phone.
   * It requires a Lambda function to be setup to autoconfirm the users email
   * on registration.
   *
   * @return bool
   *   TRUE if we are using auto confirm otherwise FALSE.
   *
   * @see https://docs.aws.amazon.com/cognito/latest/developerguide/user-pool-lambda-pre-sign-up.html#aws-lambda-triggers-pre-registration-example-2
   */
  protected function usesAutoConfirm() {
    return (bool) $this->config('cognito.settings')->get('auto_confirm_enabled');
  }

}
