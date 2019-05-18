<?php

namespace Drupal\change_pwd_page\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for user routes.
 */
class ChangePwdPageController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(DateFormatterInterface $date_formatter, UserStorageInterface $user_storage, UserDataInterface $user_data) {
    $this->dateFormatter = $date_formatter;
    $this->userStorage = $user_storage;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('date.formatter'), $container->get('entity.manager')->getStorage('user'), $container->get('user.data')
    );
  }

  /**
   * Returns the user password reset page.
   *
   * @param int $uid
   *   UID of user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The form structure or a redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the login link is for a blocked user or invalid user ID.
   */
  public function resetPass($uid, $timestamp, $hash) {
    $account = $this->currentUser();
    $config = $this->config('user.settings');
    // When processing the one-time login link, we have to make sure that a user
    // isn't already logged in.
    if ($account->isAuthenticated()) {
      // The current user is already logged in.
      if ($account->id() == $uid) {
        user_logout();
      }
      // A different user is already logged in on the computer.
      else {
        if ($reset_link_user = $this->userStorage->load($uid)) {
          drupal_set_message($this->t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.', ['%other_user' => $account->getUsername(), '%resetting_user' => $reset_link_user->getUsername(), ':logout' => $this->url('user.logout')]), 'warning');
        }
        else {
          // Invalid one-time link specifies an unknown user.
          drupal_set_message($this->t('The one-time login link you clicked is invalid.'), 'error');
        }
        return $this->redirect('<front>');
      }
    }
    // The current user is not logged in, so check the parameters.
    // Time out, in seconds, until login URL expires.
    $timeout = $config->get('password_reset_timeout');
    $current = REQUEST_TIME;

    /* @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);

    // Verify that the user exists and is active.
    if ($user && $user->isActive()) {
      // No time out for first time login.
      if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
        drupal_set_message($this->t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'), 'error');
        return $this->redirect('user.pass');
      }
      elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current) && Crypt::hashEquals($hash, user_pass_rehash($user, $timestamp))) {
        $expiration_date = $user->getLastLoginTime() ? $this->dateFormatter->format($timestamp + $timeout) : NULL;
        return $this->formBuilder()->getForm('Drupal\change_pwd_page\Form\ChangePasswordResetForm', $user, $expiration_date, $timestamp, $hash);
      }
      else {
        drupal_set_message($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'error');
        return $this->redirect('user.pass');
      }
    }
    // Blocked or invalid user ID, so deny access. The parameters will be in the
    // watchdog's URL for the administrator to check.
    throw new AccessDeniedHttpException();
  }

}
