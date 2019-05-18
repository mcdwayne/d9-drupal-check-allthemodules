<?php

namespace Drupal\cognito\Form\Email;

use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\Plugin\cognito\CognitoFlowInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Email;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The new password form.
 *
 * New password form is used after a admin registration to let the user set
 * their new password using the temporary password provided.
 */
class NewPasswordForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The cognito service.
   *
   * @var \Drupal\cognito\Aws\Cognito
   */
  protected $cognito;

  /**
   * PassResetForm constructor.
   *
   * @param \Drupal\cognito\Aws\CognitoInterface $cognito
   *   The cognito service.
   */
  public function __construct(CognitoInterface $cognito) {
    $this->cognito = $cognito;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cognito.aws')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cognito_email_new_password_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#size' => 60,
      '#maxlength' => Email::EMAIL_MAX_LENGTH,
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];

    $form['temporary_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Temporary Password'),
      '#description' => $this->t('This is the password you were emailed when your account was created.'),
    ];

    $form['new_password'] = [
      '#type' => 'password_confirm',
      '#size' => 25,
      '#description' => $this->t('Please enter your new password.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Update Password')];

    $form['#validate'][] = '::validateForgotPassword';

    return $form;
  }

  /**
   * Validation the forgot password request.
   */
  public function validateForgotPassword(array &$form, FormStateInterface $form_state) {
    $username = $form_state->getValue('mail');
    $temporary_password = trim($form_state->getValue('temporary_password'));
    $new_password = trim($form_state->getValue('new_password'));

    // Initiate the auth to ensure they do need to send a challenge. We need to
    // do this currently to get the session key but we could save it in the
    // users data when the admin registers the user account to avoid the extra
    // API call.
    $authorizeResult = $this->cognito->authorize($username, $temporary_password);

    if (!$authorizeResult->isChallenge()) {
      $form_state->setErrorByName(NULL, $this->t('You do not need to update your password.'));
      return;
    }

    // Respond to the challenge with the temporary password.
    $challengeResult = $this->cognito->adminRespondToNewPasswordChallenge($username, CognitoFlowInterface::NEW_PASSWORD_REQUIRED, $temporary_password, $authorizeResult->getResult()['Session']);

    // If they successfully responded to the challenge, then attempt to update
    // their password.
    if (!$challengeResult->hasError()) {
      $accessToken = $challengeResult->getResult()['AuthenticationResult']['AccessToken'];
      $cognitoChangeResult = $this->cognito->changePassword($accessToken, $temporary_password, $new_password);

      if ($cognitoChangeResult->hasError()) {
        $form_state->setErrorByName(NULL, $cognitoChangeResult->getError());
      }
    }
    else {
      $form_state->setErrorByName(NULL, $challengeResult->getError());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage($this->t('Your password has been updated. Please login.'));
    $form_state->setRedirect('user.login');
  }

}
