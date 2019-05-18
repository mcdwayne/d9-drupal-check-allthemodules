<?php

namespace Drupal\passwordless\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Controller\UserController;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides route responses for the Passwordless module.
 */
class PasswordlessController extends ControllerBase {
  /**
   * Returns the help page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function helpPage() {
    return array(
      '#markup' => _passwordless_text('help_text')
    );
  }

  /**
   * Returns the help page title.
   *
   * @return string
   *   The page title, retrieved from settings.
   */
  public function helpPageTitle() {
    return _passwordless_text('help_link_text');
  }

  /**
   * Checks access to the help page based on whether the current user
   * can access content, and whether the help page is enabled in settings.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function helpPageAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('access content') && !empty($this->getConfig()->get('passwordless_show_help')));
  }

    /**
   * Returns the content of the confirmation page.
   *
   * @return array
   *   A simple renderable array.
   *
   * @todo Find alternative to global $user.
   */
  public function sentPage() {
    global $user;
    $http_referrer = check_url($_SERVER['HTTP_REFERER']);

    if (
      !$user->hasPermission('configure passwordless settings') &&
      $http_referrer != \Drupal::url('user.page', [], ['absolute' => TRUE]) &&
      $http_referrer != \Drupal::url('user.login', [], ['absolute' => TRUE])
    ) {
      return new RedirectResponse(\Drupal::url('user.page'));
    }
    else {
      return array(
        '#markup' => _passwordless_text('sent_page_text')
      );
    }
  }

  /**
   * Returns the confirmation page title.
   *
   * @return string
   *   The page title, retrieved from settings.
   */
  public function sentPageTitle() {
    return _passwordless_text('sent_title_text');
  }

  /**
   * Checks access based on whether the current user is anonymous,
   * or has permission to configure the module.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function sentPageAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAnonymous() || $account->hasPermission('configure passwordless settings'));
  }

  public function redirectUserPassPage() {
    return new RedirectResponse(\Drupal::url('user.page'));
  }

  /**
   * Returns Passwordless settings.
   */
  public function getConfig() {
    return parent::config('passwordless.settings');
  }
}