<?php

namespace Drupal\quicker_login\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\SessionManagerInterface;

/**
 * QuickerLogin service.
 */
class QuickerLoginService implements QuickerLoginServiceInterface {

  /**
   * Entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Module handler.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Session manager.
   *
   * @var Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, ModuleHandlerInterface $module_handler, SessionManagerInterface $session_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->sessionManager = $session_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loginUserName($user_name) {
    $users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['name' => $user_name]);
    if (!$users) {
      return FALSE;
    }
    $target_user = reset($users);

    if ($this->currentUser->isAnonymous()) {
      // Log out the current user.
      $this->moduleHandler->invokeAll('user_logout', [$user]);
      $this->sessionManager->destroy();
    }

    user_login_finalize($target_user);

    return TRUE;
  }

}
