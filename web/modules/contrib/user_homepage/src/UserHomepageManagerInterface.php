<?php

namespace Drupal\user_homepage;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for common functionality to manage users homepages.
 */
interface UserHomepageManagerInterface {

  /**
   * Returns the path of the homepage for a given user.
   *
   * @param string $uid
   *   ID of the user whose homepage is being fetched.
   *
   * @return string
   *   The homepage path for the specified user, if it exists. NULL otherwise.
   */
  public function getUserHomepage($uid);

  /**
   * Sets the homepage path for a given user.
   *
   * @param int $uid
   *   ID of the user whose homepage is going to be stored.
   * @param string $path
   *   Path of the homepage to be set.
   *
   * @return bool
   *   TRUE if the homepage was successfully set, FALSE otherwise.
   */
  public function setUserHomepage($uid, $path);

  /**
   * Deletes the homepage path of a given user.
   *
   * @param int $uid
   *   ID of the user whose homepage is going to be unset.
   *
   * @return bool
   *   TRUE if the homepage was successfully unset, FALSE otherwise.
   */
  public function unsetUserHomepage($uid);

  /**
   * Returns the internal path of the current page, including query parameters.
   *
   * @return string
   *   The internal path that corresponds to the current page.
   */
  public function buildHomepagePathFromCurrentRequest();

  /**
   * Sets the user homepage (if any), as the 'destination' param in the request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account of the user for whom to resolve the redirection.
   *
   * @return bool
   *   TRUE if the user has a homepage and is being redirected, FALSE otherwise.
   */
  public function resolveUserRedirection(AccountInterface $account);

}
