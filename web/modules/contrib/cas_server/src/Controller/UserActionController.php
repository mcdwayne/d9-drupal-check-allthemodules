<?php

/**
 * @file
 * Contains \Drupal\cas_server\Controller\UserActionController.
 */

namespace Drupal\cas_server\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\cas_server\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\cas_server\Configuration\ConfigHelper;
use Drupal\cas_server\Ticket\TicketFactory;
use Drupal\cas_server\Ticket\TicketStorageInterface;
use Drupal\cas_server\Logger\DebugLogger;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;

/**
 * Class UserActionController.
 */
class UserActionController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Used to get the query string parameters from the request.
   *
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * The current user, or anonymous.
   *
   * @var AccountProxyInterface
   */
  protected $account;

  /**
   * The configuration helper.
   *
   * @var ConfigHelper
   */
  protected $configHelper;

  /**
   * The ticket factory.
   *
   * @var TicketFactory
   */
  protected $ticketFactory;

  /**
   * The ticket storage.
   *
   * @var TicketStorageInterface
   */
  protected $ticketStore;

  /**
   * The logger.
   *
   * @var DebugLogger
   */
  protected $logger;

  /**
   * The session manager.
   *
   * @var SessionManagerInterface;
   */
  protected $sessionManager;

  /**
   * The page cache kill switch.
   *
   * @var KillSwitch
   */
  protected $killSwitch;

  /**
   * Constructor.
   *
   * @param RequestStack $request_stack
   *   Symfony request stack.
   * @param AccountProxyInterface $user
   *   The current user.
   * @param ConfigHelper $config_helper
   *   The configuration helper.
   * @param TicketFactory $ticket_factory
   *   The ticket factory.
   * @param TicketStorageInterface $ticket_store
   *   The ticket store.
   * @param DebugLogger $debug_logger
   *   The logger.
   * @param TranslationInterface $translation
   *   The string translation service.
   * @param SessionManagerInterface $session_manager
   *   The session manager.
   * @param KillSwitch $kill_switch
   *   The page cache kill switch.
   */
  public function __construct(RequestStack $request_stack, AccountProxyInterface $user, ConfigHelper $config_helper, TicketFactory $ticket_factory, TicketStorageInterface $ticket_store, DebugLogger $debug_logger, TranslationInterface $translation, SessionManagerInterface $session_manager, KillSwitch $kill_switch) {
    $this->requestStack = $request_stack;
    $this->account = $user;
    $this->configHelper = $config_helper;
    $this->ticketFactory = $ticket_factory;
    $this->ticketStore = $ticket_store;
    $this->logger = $debug_logger;
    $this->stringTranslation = $translation;
    $this->sessionManager = $session_manager;
    $this->killSwitch = $kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('request_stack'), $container->get('current_user'), $container->get('cas_server.config_helper'), $container->get('cas_server.ticket_factory'), $container->get('cas_server.storage'), $container->get('cas_server.logger'), $container->get('string_translation'), $container->get('session_manager'), $container->get('page_cache_kill_switch'));
  }

  /**
   * Handles a page request for /cas/login.
   */
  public function login() {
    $this->killSwitch->trigger();
    $request = $this->requestStack->getCurrentRequest();
    $service = $request->query->has('service') ? $request->query->get('service') : NULL;

    // If we have a ticket, it is because we've already processed the form and
    // need to be redirected back to the service.
    if ($request->query->has('ticket') && $this->configHelper->checkServiceAgainstWhitelist($service)) {
      $url = Url::fromUri($service, ['query' => ['ticket' => $request->query->get('ticket')]]);
      return RedirectResponse::create($url->toString(), 302);
    }

    if ($request->query->has('gateway')) {
      if ($request->query->get('gateway') == 'false') {
        $gateway = FALSE;
      }
      else {
        $gateway = (bool)$request->query->get('gateway');
      }
    }
    else {
      $gateway = FALSE;
    }

    if ($request->query->has('renew')) {
      if ($request->query->get('renew') == 'false') {
        $renew = FALSE;
      }
      else {
        $renew = (bool)$request->query->get('renew');
      }
    }
    else {
      $renew = FALSE;
    }

    // If no service, need to either show the login form (if not logged in),
    // or a simple page to logged in users explaining their state.
    if (is_null($service)) {
      if (!$this->userHasSingleSignOnSession(NULL)) {
        return \Drupal::formBuilder()->getForm('\Drupal\cas_server\Form\UserLogin', '');
      }
      else {
        return $this->generateLoggedInMessage();
      }
    }

    // Check service against whitelist. If its not a valid service, display
    // a page to that effect.
    if (!$this->configHelper->checkServiceAgainstWhitelist($service)) {
      return $this->generateInvalidServiceMessage();
    }

    // If user has an active single sign on session and renew is not set,
    // generate a service ticket and redirect.
    if (!$renew && $this->userHasSingleSignOnSession($service)) {
      $st = $this->ticketFactory->createServiceTicket($service, FALSE);
      $url = Url::fromUri($service, ['query' => ['ticket' => $st->getId()]]);
      return RedirectResponse::create($url->toString(), 302);
    }
    
    // If gateway is set and user is not logged in, redirect them back to
    // service.
    if ($gateway && !$this->userHasSingleSignOnSession($service)) {
      return RedirectResponse::create($service, 302);
    }

    // Present the user with a login form.
    return \Drupal::formBuilder()->getForm('\Drupal\cas_server\Form\UserLogin', $service);

  }

  /**
   * Handles a page request for /cas/logout.
   */
  public function logout() {
    $this->killSwitch->trigger();
    if (isset($_COOKIE['cas_tgc'])) {
      unset($_COOKIE['cas_tgc']);
      setcookie('cas_tgc', '', REQUEST_TIME - 3600, '/cas');
    }
    $session_id = $this->sessionManager->getId();
    $hashed_id = Crypt::hashBase64($session_id);
    $this->ticketStore->deleteTicketsBySession($hashed_id);
    $this->userLogout();

    return $this->generateUserLogoutPage();
  }

  /**
   * Whether or not a user has a valid single sign on session for a given service.
   *
   * @param string $service
   *   The service to check for.
   *
   * @return bool
   */
  private function userHasSingleSignOnSession($service) {
    if (!is_null($service) && !$this->configHelper->verifyServiceForSso($service)) {
      return FALSE;
    }

    if ($this->configHelper->shouldUseTicketGrantingTicket() && isset($_COOKIE['cas_tgc'])) {
      try {
        $tgt = $this->ticketStore->retrieveTicketGrantingTicket(urldecode($_COOKIE['cas_tgc']));
      }
      catch (TicketTypeException $e) {
        $this->logger->log($e->getMessage());
        return FALSE;
      }
      catch (TicketMissingException $e) {
        $this->logger->log("Ticket not found " . urldecode($_COOKIE['cas_tgc']));
        return FALSE;
      }

      if (REQUEST_TIME > $tgt->getExpirationTime()) {
        $this->ticketStore->deleteTicketGrantingTicket($tgt);
        return FALSE;
      }

      if ($this->account->id() != $tgt->getUid()) {
        return FALSE;
      }

      return TRUE;
    }
    elseif (!$this->configHelper->shouldUseTicketGrantingTicket() && !$this->account->isAnonymous()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Markup for an invalid service message.
   *
   * @return array
   *   A renderable array.
   */
  private function generateInvalidServiceMessage() {
    $output['header'] = ['#markup' => '<h2>' . $this->t('Invalid Service') . '</h2>'];
    $m = $this->configHelper->getInvalidServiceMessage();
    $message = $m ? $m : $this->t('You have not requested a valid service.');
    $output['message'] = ['#markup' => $message];

    return $output;
  }

  /**
   * Markup for logout message.
   *
   * @return array
   *   A renderable array.
   */
  private function generateUserLogoutPage() {
    $m = $this->configHelper->getUserLogoutMessage();
    $message = $m ? $m : $this->t('You have been logged out');
    $output['message'] = ['#markup' => $message];

    return $output;
  }

  /**
   * Markup for logged in message.
   *
   * @return array
   *   A renderable array.
   */
  private function generateLoggedInMessage() {
    $m = $this->configHelper->getLoggedInMessage();
    $message = $m ? $m : $this->t('You are logged in to CAS single sign on.');
    $output['message'] = ['#markup' => $message];

    return $output;
  }


  /**
   * Encapsulates user_logout.
   */
  private function userLogout() {
    user_logout();
  }
}
