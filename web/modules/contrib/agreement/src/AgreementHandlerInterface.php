<?php

namespace Drupal\agreement;

use Drupal\agreement\Entity\Agreement;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;

/**
 * Agreement handler interface.
 */
interface AgreementHandlerInterface {

  /**
   * Check the status of an user account for a particular agreement.
   *
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement to check if a user has agreed.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account to check.
   *
   * @return bool
   *   TRUE if the user account has agreed to this agreement.
   */
  public function hasAgreed(Agreement $agreement, AccountProxyInterface $account);

  /**
   * Get the last agreement for the user for the agreement.
   *
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement to check if a user has agreed.
   * @param \Drupal\user\UserInterface $account
   *   The user account to check.
   *
   * @return int
   *   The timestamp that the user last agreed or -1 if never agreed.
   */
  public function lastAgreed(Agreement $agreement, UserInterface $account);

  /**
   * Check if an user can bypass the agreement or if the agreement applies.
   *
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement to check roles.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account to check bypass permission.
   */
  public function canAgree(Agreement $agreement, AccountProxyInterface $account);

  /**
   * Accept the agreement for an user account.
   *
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement that the user is agreeing to.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account to agree.
   * @param int $agreed
   *   An optional integer to set the agreement status to. Defaults to 1.
   *
   * @return bool
   *   TRUE if the operation was successful.
   */
  public function agree(Agreement $agreement, AccountProxyInterface $account, $agreed = 1);

  /**
   * Find the agreement by user account and path.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user account to check.
   * @param string $path
   *   The path to check.
   *
   * @return \Drupal\agreement\Entity\Agreement|false
   *   The agreement entity to use or FALSE if none found.
   */
  public function getAgreementByUserAndPath(AccountProxyInterface $account, $path);

  /**
   * Adds leading slash to a path string.
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   The new value.
   */
  public static function prefixPath($value);

}
