<?php

namespace Drupal\eid_auth\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\eid_auth\Ajax\SmartIdCheckCommand;
use Drupal\user\PrivateTempStoreFactory;
use Sk\SmartId\Api\Data\AuthenticationHash;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EidSmartIdLoginForm.
 *
 * @package Drupal\eid_auth\Form
 */
class EidSmartIdLoginForm extends FormBase {

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * TempStoreFactory service.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * PrivateTempStore object.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * SessionManager service.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * EidLoginForm constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   Url generator service.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Private temp store factory object.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   Session manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user account.
   */
  public function __construct(LoggerChannelFactoryInterface $logger,
                              UrlGeneratorInterface $url_generator,
                              PrivateTempStoreFactory $temp_store_factory,
                              SessionManagerInterface $session_manager,
                              AccountInterface $current_user) {
    $this->setLoggerFactory($logger);
    $this->setUrlGenerator($url_generator);

    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;

    $this->store = $this->tempStoreFactory->get('eid_auth.smart_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eid_auth_smart_id_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('url_generator'),
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'eid_auth/login';
    $form['#attached']['library'][] = 'eid_auth/auth-status-check-command';
    $form['#attached']['library'][] = 'eid_auth/eid-auth-option-login';

    $form['personal_id_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personal identification code'),
      '#attributes' => [
        'placeholder' => $this->t('Personal identification code'),
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['smart_id_login'] = [
      '#type' => 'submit',
      '#value' => $this->t('Smart-ID log in'),
      '#ajax' => [
        'callback' => '::smartIdCallback',
        'wrapper' => 'smart-id-login-option',
        'method' => 'html',
        'progress' => 'none',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $personal_id_code = $form_state->getValue('personal_id_code');

    if ($form_state->isValueEmpty('personal_id_code')) {
      $form_state->setErrorByName('personal_id_code', $this->t('Personal identification code must be entered!'));
    }

    // @TODO document number (PNOEE-3111111111-XXXX-XX) is not yet supported.
    if (mb_strlen($personal_id_code) != 11) {
      $form_state->setErrorByName('personal_id_code', $this->t('Personal identification code is invalid!'));
    }

    if (!is_numeric($personal_id_code)) {
      $form_state->setErrorByName('personal_id_code', $this->t('Personal identification code must contain only numbers!'));
    }
  }

  /**
   * Smart-ID authentication callback.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   *   Response object.
   */
  public function smartIdCallback(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      return $form;
    }

    // Start session when anonymous user (it should be true always).
    if ($this->currentUser->isAnonymous() && !isset($_SESSION['session_started'])) {
      $_SESSION['session_started'] = TRUE;
      $this->sessionManager->start();
    }

    $commands = new AjaxResponse();
    $personal_id_code = $form_state->getValue('personal_id_code');
    $authenticationHash = AuthenticationHash::generate();

    $this->store->set('auth_hash', $authenticationHash);
    $this->store->set('personal_id_code', $personal_id_code);

    $verificationCode = $authenticationHash->calculateVerificationCode();

    // Remove previous error messages when there are any.
    $commands->addCommand(new InvokeCommand('.alert', 'remove'));
    $commands->addCommand(new InvokeCommand('.auth-options', 'hide'));
    $commands->addCommand(new InvokeCommand('#eid-auth-progress', 'show'));
    $commands->addCommand(new HtmlCommand('#eid-auth-progress .status-code', $verificationCode));
    $commands->addCommand(new SmartIdCheckCommand());

    return $commands;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to see here.
  }

}
