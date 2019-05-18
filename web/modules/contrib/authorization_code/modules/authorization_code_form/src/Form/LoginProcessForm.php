<?php

namespace Drupal\authorization_code_form\Form;

use Drupal\authorization_code\Entity\LoginProcess;
use Drupal\authorization_code\Exceptions\FailedToSaveCodeException;
use Drupal\authorization_code\Exceptions\FailedToSendCodeException;
use Drupal\authorization_code\Exceptions\InvalidCodeException;
use Drupal\authorization_code\Exceptions\UserNotFoundException;
use Drupal\authorization_code\UserIdentifierInterface;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Login form that uses the LoginProcess entity.
 */
class LoginProcessForm extends FormBase {

  const USER_IDENTIFIER_STEP = 'USER_IDENTIFIER_STEP';

  const CODE_AUTHORIZATION_STEP = 'CODE_AUTHORIZATION_STEP';

  /**
   * The login process entity.
   *
   * @var \Drupal\authorization_code\Entity\LoginProcess
   */
  private $loginProcess;

  /**
   * The user identifier plugin.
   *
   * @var \Drupal\authorization_code\UserIdentifierInterface
   */
  private $userIdentifierPlugin;

  /**
   * The constructor.
   *
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process entity.
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   */
  public function __construct(LoginProcess $login_process) {
    $this->setupLoginProcess($login_process);
    assert($this->userIdentifierPlugin instanceof UserIdentifierInterface);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $this->_loginProcessId = $this->loginProcess->id();
    unset($this->loginProcess);

    return parent::__sleep();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   */
  public function __wakeup() {
    parent::__wakeup();
    $this->setupLoginProcess(LoginProcess::load($this->_loginProcessId));
  }

  /**
   * Sets up the login process entity and user identifier plugin.
   *
   * @param \Drupal\authorization_code\Entity\LoginProcess $login_process
   *   The login process entity.
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   */
  private function setupLoginProcess(LoginProcess $login_process) {
    $this->loginProcess = $login_process;
    $this->userIdentifierPlugin = $login_process->getPluginOrThrowException('user_identifier');
  }

  /**
   * The user identifier plugin title.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The user identifier plugin title.
   */
  private function userIdentifierPluginTitle(): MarkupInterface {
    return $this->userIdentifierPlugin->getPluginDefinition()['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['step_wrapper'] = [
      '#type' => 'container',
      '#tree' => FALSE,
      '#id' => 'step-wrapper',
    ];

    $form['step_wrapper']['step'] = $this->buildStep($form_state);
    $form['step_wrapper']['step']['#type'] = 'container';
    $form['step_wrapper']['step']['#tree'] = FALSE;

    return $form;
  }

  /**
   * Builds the step subform.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The resulting form array.
   *
   * @throws \InvalidArgumentException
   */
  private function buildStep(FormStateInterface $form_state): array {
    switch ($this->currentStep($form_state)) {
      case static::USER_IDENTIFIER_STEP:
        return $this->buildUserIdentifierStep();

      case static::CODE_AUTHORIZATION_STEP:
        return $this->buildCodeAuthorizationStep($form_state);

      default:
        throw new \InvalidArgumentException('Reached invalid step');
    }
  }

  /**
   * Returns the step_wrapper part of the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form.
   */
  public function returnStepWrapper(array &$form, FormStateInterface $form_state) {
    return $form['step_wrapper'];
  }

  /**
   * Builds the user identifier step subform.
   *
   * @return array
   *   The user identifier step subform.
   */
  private function buildUserIdentifierStep(): array {
    $step = [];

    $step['user_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->userIdentifierPluginTitle(),
    ];

    $step['actions'] = [
      '#tree' => FALSE,
      '#type' => 'actions',
      'send_code' => [
        '#type' => 'submit',
        '#value' => $this->t('Send code'),
        '#button_type' => 'primary',
        '#validate' => ['::validateFloodGatesDown'],
        '#submit' => ['::startLoginProcess'],
        '#ajax' => [
          'callback' => '::returnStepWrapper',
          'wrapper' => 'step-wrapper',
          'event' => 'click',
        ],
      ],
    ];

    return $step;
  }

  /**
   * Validates that the flood gates are down.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateFloodGatesDown(array &$form, FormStateInterface $form_state) {
    if (!$this->loginProcess->isAllowedByIpFloodGate()) {
      $form_state->setError($form, $this->ipFloodGateMessage());
    }

    $user_identifier = $form_state->getValue('user_identifier');
    if (!$this->loginProcess->isAllowedByUserFloodGate($user_identifier)) {
      $form_state->setError($form, $this->userFloodGateMessage($user_identifier));
    }
  }

  /**
   * The ip flood gate message.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The ip flood gate message.
   */
  private function ipFloodGateMessage(): MarkupInterface {
    return $this->t('Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href=":url">request a new password</a>.',
      [
        '%ip' => $this->getRequest()->getClientIp() ?: 'Unknown',
        ':url' => Url::fromRoute('user.pass')->toString(),
      ]
    );
  }

  /**
   * The ip flood gate message.
   *
   * @param mixed $user_identifier
   *   The user identifier.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The ip flood gate message.
   */
  private function userFloodGateMessage($user_identifier): MarkupInterface {
    return $this->t('Too many failed login attempts for your account (%identifier). This account is temporarily blocked. Try again later or <a href=":url">request a new password</a>.',
      [
        '%identifier' => $user_identifier,
        ':url' => Url::fromRoute('user.pass')->toString(),
      ]
    );
  }

  /**
   * Starts the login process.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   */
  public function startLoginProcess(array &$form, FormStateInterface $form_state) {
    $user_identifier = $form_state->getValue('user_identifier');
    try {
      $this->loginProcess->startLoginProcess($user_identifier);
      $this->messenger()->addStatus($this->codeSentMessage($form_state));
      $this->resetStepAndRebuild($form_state, static::CODE_AUTHORIZATION_STEP);
    }
    catch (UserNotFoundException $e) {
      // Increment step even if user not found to hide that fact.
      $this->resetStepAndRebuild($form_state, static::CODE_AUTHORIZATION_STEP);
      $this->logger('authorization_code')
        ->warning('Failed login attempt - User not found');
    }
    catch (FailedToSendCodeException $e) {
      $this->handleFailedToSendOrSaveCode($e);
    }
    catch (FailedToSaveCodeException $e) {
      $this->handleFailedToSendOrSaveCode($e);
    }
  }

  /**
   * The code-sent message.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The code-sent message.
   */
  private function codeSentMessage(FormStateInterface $form_state): MarkupInterface {
    // TODO: make this configurable!
    return $this->t('Authorization code sent to %identifier', [
      '%identifier' => $form_state->getValue('user_identifier'),
    ]);
  }

  /**
   * Handles when failed to send or save code.
   *
   * @param \Exception $e
   *   The exception Object thrown while sending or saving the code.
   */
  private function handleFailedToSendOrSaveCode(\Exception $e) {
    $this->logger('authorization_code')
      ->error('Failed to send/save authorization code.<br> Message: @message <br><pre>@trace</pre>', [
        '@message' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);
    $this->messenger()->addError($this->failedToSendCodeMessage());
  }

  /**
   * The failed-to-send-code message.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The failed-to-send-code message.
   */
  private function failedToSendCodeMessage(): MarkupInterface {
    // TODO: make this configurable!
    return $this->t('An unexpected error occurred while sending the authorization code. If this message persists, please contact the site administrator.');
  }

  /**
   * Builds the code authorization step subform.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The subform array.
   */
  private function buildCodeAuthorizationStep(FormStateInterface $form_state) {
    $step = [];

    $step['user_identifier'] = [
      '#type' => 'value',
      '#value' => $form_state->getValue('user_identifier'),
    ];

    $step['code'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Authorization code'),
    ];

    $step['actions'] = [
      '#tree' => FALSE,
      '#type' => 'actions',
      'login' => [
        '#type' => 'submit',
        '#value' => $this->t('Login'),
        '#button_type' => 'primary',
        '#validate' => ['::validateFloodGatesDown', '::validateCode'],
        '#submit' => ['::completeLoginProcess'],
        '#ajax' => [
          'callback' => '::redirectIfLoggedIn',
          'wrapper' => 'step-wrapper',
          'event' => 'click',
        ],
      ],
      'resend_code' => [
        '#type' => 'submit',
        '#value' => $this->t('Send new code'),
        '#limit_validation_errors' => [['user_identifier']],
        '#validate' => ['::validateFloodGatesDown'],
        '#submit' => ['::startLoginProcess'],
        '#ajax' => [
          'callback' => '::returnStepWrapper',
          'wrapper' => 'step-wrapper',
          'event' => 'click',
        ],
      ],
      'back' => [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#limit_validation_errors' => [['user_identifier']],
        '#validate' => [],
        '#submit' => ['::backToUserIdentifierStep'],
        '#ajax' => [
          'callback' => '::returnStepWrapper',
          'wrapper' => 'step-wrapper',
          'event' => 'click',
        ],
      ],
    ];

    return $step;
  }

  /**
   * Validates the code.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   */
  public function validateCode(array &$form, FormStateInterface $form_state) {
    $user_identifier = $form_state->getValue('user_identifier');
    $code = $form_state->getValue('code');

    try {
      $this->loginProcess->throwAndRegisterIfInvalidCode($user_identifier, $code);
    }
    catch (UserNotFoundException $e) {
      // Nothing to do if the user is not found.
    }
    catch (InvalidCodeException $e) {
      $form_state->setErrorByName('code', 'This code is invalid.');
    }
  }

  /**
   * Completes the login process.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function completeLoginProcess(array &$form, FormStateInterface $form_state) {
    $user_identifier = $form_state->getValue('user_identifier');
    $code = $form_state->getValue('code');
    $user = $this->loginProcess->completeLoginProcess($user_identifier, $code);

    $this->getRedirectDestination()->set(
      $this->getRequest()->request->get('destination',
        $user->toUrl()->toString()));
  }

  /**
   * Redirects the user to the login success page.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   The redirect command.
   */
  public function redirectIfLoggedIn(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getErrors())) {
      return $this->returnStepWrapper($form, $form_state);
    }

    return ($response = new AjaxResponse())
      ->addCommand(new RedirectCommand($this->getRedirectDestination()->get()));
  }

  /**
   * Returns to the user-identifier step.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function backToUserIdentifierStep(array &$form, FormStateInterface $form_state) {
    $this->resetStepAndRebuild($form_state, static::USER_IDENTIFIER_STEP);
  }

  /**
   * This form should be submitted using completeLoginProcess.
   *
   * @see completeLoginProcess
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    throw new \LogicException('Unreachable point');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'authorization_code_form_login_process:' . $this->loginProcess->id();
  }

  /**
   * The current step.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return string
   *   The current step.
   */
  private function currentStep(FormStateInterface $form_state) {
    return $form_state->get('current_step') ?: static::USER_IDENTIFIER_STEP;
  }

  /**
   * Resets the step and rebuilds the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $step
   *   The next step.
   */
  private function resetStepAndRebuild(FormStateInterface $form_state, string $step) {
    $form_state->set('current_step', $step);
    $form_state->setRebuild();
  }

}
