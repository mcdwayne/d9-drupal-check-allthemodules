<?php

namespace Drupal\userswitch;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Defines a UserSwitch service to switch user account.
 */
class UserSwitch {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The session manager.
   *
   * @var Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * Constructs.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param Symfony\Component\HttpFoundation\Session\Session $session
   *   The session manager.
   */
  public function __construct(AccountInterface $current_user, ModuleHandlerInterface $module_handler, SessionManagerInterface $session_manager, EntityTypeManagerInterface $entityTypeManager, Session $session) {
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->sessionManager = $session_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->session = $session;
  }

  /**
   * Check session.
   *
   * @return bool
   *   TRUE when user switch account, FALSE otherwise.
   */
  public function isSwitchUser() {
    return !empty($_SESSION['SwitchCurrentUser']);
  }

  /**
   * Return original user id, FALSE otherwise.
   */
  public function getUserId() {
    if (isset($_SESSION['SwitchCurrentUser'])) {
      return $_SESSION['SwitchCurrentUser'];
    }
    else {
      return FALSE;
    }
  }

  /**
   * User account switch.
   */
  public function switchToOther($target_account) {
    $account = $this->currentUser->getAccount();
    $this->moduleHandler->invokeAll('user_logout', [$account]);
    $this->sessionManager->regenerate();
    $_SESSION['SwitchCurrentUser'] = $account->id();
    $t_account = $this->entityTypeManager
      ->getStorage('user')
      ->load($target_account);
    $this->currentUser->setAccount($t_account);
    $this->session->set('uid', $t_account->id());
    $this->moduleHandler->invokeAll('user_login', [$t_account]);
    return TRUE;
  }

  /**
   * Switching back to previous user.
   *
   * @return bool
   *   TRUE when switched back previous account.
   */
  public function switchUserBack() {
    if (empty($_SESSION['SwitchCurrentUser'])) {
      return FALSE;
    }
    $new_user = $this->entityTypeManager
      ->getStorage('user')
      ->load($_SESSION['SwitchCurrentUser']);
    unset($_SESSION['SwitchCurrentUser']);
    if (!$new_user) {
      return FALSE;
    }
    $account = $this->currentUser->getAccount();
    $this->moduleHandler->invokeAll('user_logout', [$account]);
    $this->sessionManager->regenerate();
    $this->currentUser->setAccount($new_user);
    $this->session->set('uid', $new_user->id());
    $this->moduleHandler->invokeAll('user_login', [$new_user]);
    return TRUE;
  }

}
