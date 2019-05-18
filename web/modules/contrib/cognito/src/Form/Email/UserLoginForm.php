<?php

namespace Drupal\cognito\Form\Email;

use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\CognitoFlowManagerInterface;
use Drupal\cognito\CognitoMessagesInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\externalauth\ExternalAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The user login form.
 */
class UserLoginForm extends FormBase {

  /**
   * The cognito service.
   *
   * @var \Drupal\cognito\Aws\Cognito
   */
  protected $cognito;

  /**
   * The messages service.
   *
   * @var \Drupal\cognito\CognitoMessages
   */
  protected $cognitoMessages;

  /**
   * The external auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * The cognito flow manager.
   *
   * @var \Drupal\cognito\CognitoFlowManagerInterface
   */
  protected $cognitoFlowManager;

  /**
   * UserLoginForm constructor.
   *
   * @param \Drupal\cognito\Aws\CognitoInterface $cognito
   *   The cognito service.
   * @param \Drupal\cognito\CognitoMessagesInterface $cognitoMessages
   *   The cognito messages service.
   * @param \Drupal\cognito\CognitoFlowManagerInterface $cognitoFlowManager
   *   The cognito flow plugin manager.
   * @param \Drupal\externalauth\ExternalAuthInterface $externalAuth
   *   The external auth service.
   */
  public function __construct(CognitoInterface $cognito, CognitoMessagesInterface $cognitoMessages, CognitoFlowManagerInterface $cognitoFlowManager, ExternalAuthInterface $externalAuth) {
    $this->cognito = $cognito;
    $this->cognitoMessages = $cognitoMessages;
    $this->cognitoFlowManager = $cognitoFlowManager;
    $this->externalAuth = $externalAuth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cognito.aws'),
      $container->get('cognito.messages'),
      $container->get('plugin.manager.cognito.cognito_flow'),
      $container->get('externalauth.externalauth')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#size' => 60,
      '#maxlength' => Email::EMAIL_MAX_LENGTH,
      '#description' => $this->t('Enter your email address.'),
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];

    $form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 60,
      '#description' => $this->t('Enter the password that accompanies your username.'),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Log in')];

    $form['#validate'][] = '::validateAuthentication';

    return $form;
  }

  /**
   * Validate authentication against Cognito.
   */
  public function validateAuthentication(array &$form, FormStateInterface $form_state) {
    $mail = strtolower($form_state->getValue('mail'));
    $password = trim($form_state->getValue('pass'));

    // We validate the users local status as well because it's possible in the
    // future, status updates will be async but we want it to take immediate
    // effect locally.
    if (user_is_blocked($mail)) {
      $form_state->setErrorByName('mail', $this->cognitoMessages->accountBlocked());
      return;
    }

    $errors = $form_state->getErrors();
    if ($errors) {
      return;
    }

    $result = $this->cognito->authorize($mail, $password);

    if ($result->isChallenge()) {
      $flow = $this->cognitoFlowManager->getSelectedFlow();
      $route = $flow->getChallengeRoute($result->getResult()['ChallengeName']);
      $form_state->setRedirect($route);
    }
    elseif ($result->hasError()) {
      if ($result->getErrorCode() === 'PasswordResetRequiredException') {
        $form_state->setErrorByName(NULL, new FormattableMarkup($this->cognitoMessages->passwordResetRequired(), []));
      }
      else {
        $form_state->setErrorByName(NULL, $result->getError());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->externalAuth->login($form_state->getValue('mail'), 'cognito');
  }

}
