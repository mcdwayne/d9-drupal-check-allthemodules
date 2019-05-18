<?php

/**
 * @file
 * Contains \Drupal\cas\Form\UserLogin.
 */

namespace Drupal\cas_server\Form;

use Drupal\cas_server\Logger\DebugLogger;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Utility\Crypt;
use Drupal\cas_server\Ticket\TicketFactory;
use Drupal\cas_server\Configuration\ConfigHelper;
use Drupal\Core\Url;
use Drupal\Core\Session\SessionManagerInterface;

/**
 * Class UserLogin.
 *
 * @codeCoverageIgnore
 */
class UserLogin extends FormBase {

  /**
   * Constructs a \Drupal\cas_server\Form\UserLogin object.
   *
   * @param UserAuthInterface $user_auth
   *   The authentication provider.
   * @param TicketFactory $ticket_factory
   *   The ticket factory.
   * @param ConfigHelper $config_helper
   *   The configuration helper.
   * @param SessionManagerInterface
   *   The session manager.
   * @param RequestStack $request_stack
   *   The Symfony request stack.
   * @param DebugLogger $debug_logger
   *   The logger.
   * @param EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(UserAuthInterface $user_auth, TicketFactory $ticket_factory, ConfigHelper $config_helper, SessionManagerInterface $session_manager, RequestStack $request_stack, DebugLogger $debug_logger, EntityTypeManagerInterface $entity_manager) {
    $this->authService = $user_auth;
    $this->ticketFactory = $ticket_factory;
    $this->configHelper = $config_helper;
    $this->sessionManager = $session_manager;
    $this->requestStack = $request_stack;
    $this->logger = $debug_logger;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.auth'),
      $container->get('cas_server.ticket_factory'),
      $container->get('cas_server.config_helper'),
      $container->get('session_manager'),
      $container->get('request_stack'),
      $container->get('cas_server.logger'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cas_server_user_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $service = '') {
    $this->sessionManager->start();
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->configHelper->getAuthenticationSourceField() == 'both' ? $this->t('Username or email address') : ($this->configHelper->getAuthenticationSourceField() == 'email' ? $this->t('Email address') : $this->t('Username')),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    );

    $form['password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 60,
      '#required' => TRUE,
    );

    // Only regenerate lt if we're here on an initial build, not a submission.
    $request = $this->requestStack->getCurrentRequest();

    if ($request->getMethod() != 'POST') {
      $lt = 'LT-' . Crypt::randomBytesBase64(32);
      $_SESSION['cas_lt'] = $lt;
    }

    $form['lt'] = array(
      '#type' => 'hidden',
      '#value' => isset($_SESSION['cas_lt']) ? $_SESSION['cas_lt'] : '',
    );

    $form['service'] = array(
      '#type' => 'hidden',
      '#value' => $service,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );

    if ($this->configHelper->getShowResetPassword()) {
      $form['request_password'] = [
        '#theme' => 'item_list',
        '#items' => [
          [
            '#type' => 'link',
            '#title' => $this->t('Reset your password'),
            '#url' => Url::fromRoute('user.pass', [], [
              'attributes' => [
                'title' => $this->t('Send password reset instructions via email.'),
                'class' => ['request-password-link'],
              ],
            ]),
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $username = trim($form_state->getValue('username'));
    if ($this->configHelper->getAuthenticationSourceField() != 'name') {
      $users = $this->entityManager->getStorage('user')->loadByProperties(['mail' => $username]);
      if ($users) {
        $user = reset($users);
        $username = $user->getAccountName();

      }
      elseif ($this->configHelper->getAuthenticationSourceField() == 'mail') {
        $form_state->setErrorByName('username', $this->t('Invalid username or password. Please try again.'));
        return;
      }
    }
    $password = trim($form_state->getValue('password'));
    if (!($uid = $this->authService->authenticate($username, $password))) {
      $form_state->setErrorByName('username', $this->t('Invalid username or password. Please try again.'));
    }
    else {
      $form_state->setValue('uid', $uid);
      $form_state->setValue('username', $username);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ((empty($_SESSION['cas_lt'])) || $form_state->getValue('lt') != $_SESSION['cas_lt']) {
      drupal_set_message($this->t('Login ticket invalid. Please try again.'), 'error');
      $form_state->setRedirectUrl(Url::fromRoute('cas_server.login'));
    }
    else {
      $service = $form_state->getValue('service');
      if ($uid = $form_state->getValue('uid')) {
        $account = $this->entityManager->getStorage('user')->load($uid);
        user_login_finalize($account);
        if (empty($service) || $this->configHelper->verifyServiceForSso($service)) {
          if ($this->configHelper->shouldUseTicketGrantingTicket()) {
            $tgt = $this->ticketFactory->createTicketGrantingTicket();
            setcookie('cas_tgc', $tgt->getId(), REQUEST_TIME + $this->configHelper->getTicketGrantingTicketTimeout(), '/cas');
          }
        }
        if (!empty($service)) {
          $st = $this->ticketFactory->createServiceTicket($service, TRUE);
          $url = Url::fromRoute('cas_server.login', [], ['query' => ['service' => $service, 'ticket' => $st->getId()]]);
          $form_state->setRedirectUrl($url);
        }
        else {
          $form_state->setRedirectUrl(Url::fromRoute('cas_server.login'));
        }
      }
      else {
        drupal_set_message($this->t('Bad username/password combination given.'), 'error');
        $form_state->setRedirectUrl(Url::fromRoute('cas_server.login'));
      }
    }

  }

}
