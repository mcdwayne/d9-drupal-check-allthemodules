<?php

/**
 * @file
 * Contains \Drupal\password_reset_tabs\Controller\IndexController.
 */

namespace Drupal\password_reset_tabs\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Drupal\Component\Utility\Crypt;
use Drupal\user\Controller\UserController;
use Drupal\user\Entity\User;

/**
 * Controller.
 */
class IndexController extends UserController {
  /**
   * Check hashed token, if fails redirect user.
   */
  public function passwordResetTabsValidation() {
    $token_generator = \Drupal::csrfToken();
    $query = \Drupal::request()->query->all();
    $token = $query['query']['token'];
    if ($token_generator->validate($token, 'pass_valid_hash')) {
      $element = array(
        '#markup' => 'Further instructions have been sent to your e-mail address',
        '#attached' => array(
          'library' => array(
            'password_reset_tabs/tabs-js',
          ),
        ),
      );
      return $element;
    }
    else {
      return new RedirectResponse(\Drupal::url('password_reset_tabs.provide_tabs'));

    }
  }
  /**
   * Returns user edit form.
   */
  public function passwordResetTabsNewPassword() {
    $account = \Drupal::currentUser();
    if ($account->isAuthenticated()) {
      $user_id = $account->id();
      $user = User::load($user_id);
      return \Drupal::service('entity.form_builder')->getForm($user, 'default');
    }
    else {
      return new RedirectResponse(\Drupal::url('password_reset_tabs.provide_tabs'));
    }
  }
  /**
   * Returns successfull message after password reset.
   */
  public function passwordResetTabsDone() {
    $token_generator = \Drupal::csrfToken();
    $query = \Drupal::request()->query->all();
    $token = $query['query']['token'];
    if ($token_generator->validate($token, 'pass_done_hash')) {
      user_logout();
      $element = array(
        '#markup' => 'Your password changed successfully. ' .
        t('Click <a href="@login">here</a> to login.',
        array('@login' => \Drupal::url('user.login'))) . '</p>',
        '#attached' => array(
          'library' => array('password_reset_tabs/tabs-js'),
        ),
      );
      return $element;
    }
    else {
      return new RedirectResponse(\Drupal::url('password_reset_tabs.provide_tabs'));
    }
  }

  /**
   * Returns the user password reset page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int $uid
   *   UID of user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   * 
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function resetPass(Request $request, $uid, $timestamp, $hash) {
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
          drupal_set_message($this->t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href="@logout">logout</a> and try using the link again.', array(
            '%other_user' => $account->getUsername(),
            '%resetting_user' => $reset_link_user->getUsername(),
            '@logout' => $this->url('user.logout'),
          )), 'warning');
        }
        else {
          // Invalid one-time link specifies an unknown user.
          drupal_set_message($this->t('The one-time login link you clicked is invalid.'));
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
        return $this->redirect('password_reset_tabs.provide_tabs');
      }
      elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current) && ($hash === user_pass_rehash($user, $timestamp))) {
        $user = User::load($uid);
        user_login_finalize($user);
        drupal_set_message($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.'));
        $token = Crypt::randomBytesBase64(55);
        $_SESSION['pass_reset_' . $user->id()] = $token;
        return $this->redirect(
            'password_reset_tabs.new_password',
            array('user' => $user->id()),
            array(
              'query' => array('pass-reset-token' => $token),
              'absolute' => TRUE,
            )
        );
      }
      else {
        drupal_set_message($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'error');
        return $this->redirect('password_reset_tabs.provide_tabs');
      }
    }
    // Blocked or invalid user ID, so deny access. The parameters will be in the
    // watchdog's URL for the administrator to check.
    throw new AccessDeniedHttpException();
  }

}
