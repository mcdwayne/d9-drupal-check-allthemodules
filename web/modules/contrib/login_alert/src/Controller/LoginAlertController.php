<?php

namespace Drupal\login_alert\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\SessionManager;
use Drupal\user\UserStorageInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Component\Utility\Crypt;
use Psr\Log\LoggerInterface;

/**
 * LoginAlertController Controller.
 */
class LoginAlertController extends ControllerBase {

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManager
   */
  protected $serviceSession;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a LoginAlertController object.
   *
   * @param \Drupal\Core\Session\SessionManager $sessionManager
   *   The session manager.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(SessionManager $sessionManager, UserStorageInterface $user_storage, LoggerInterface $logger) {
    $this->serviceSession = $sessionManager;
    $this->userStorage = $user_storage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session_manager'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('logger.factory')->get('login_alert')
    );
  }

  /**
   * Destroy all session for the specific user.
   *
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   The login timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the $uid is for a blocked user or invalid user ID.
   */
  public function logoutUser($uid, $timestamp, $hash) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);

    // Verify that the user exists and is active.
    if ($user === NULL || !$user->isActive()) {
      // Blocked or invalid user ID, so deny access. The parameters will be in
      // the watchdog's URL for the administrator to check.
      throw new AccessDeniedHttpException();
    }
    if (Crypt::hashEquals($hash, login_alert_user_hash($user, $timestamp))) {
      $this->logger->notice('User %name was logged out.', ['%name' => $user->getDisplayName()]);
      drupal_set_message($this->t('You have just used login alert link to logout %name from the system.', ['%name' => $user->getDisplayName()]));
      $this->serviceSession->delete($uid);
    }
    return $this->redirect('<front>');
  }

}
