<?php

namespace Drupal\role_toggle;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;

abstract class AccountProxyDecoratorBase implements AccountProxyInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $decorated;

  /**
   * AccountProxyDecoratorBase constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $decorated
   */
  public function __construct(AccountProxyInterface $decorated) {
    $this->decorated = $decorated;
  }

  /**
   * @return \Drupal\Core\Session\AccountProxyInterface
   */
  public function getDecorated() {
    return $this->decorated;
  }

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $decorated
   */
  public function setDecorated(AccountProxyInterface $decorated) {
    $this->decorated = $decorated;
  }

  /**
   * @inheritDoc
   */
  public function setAccount(AccountInterface $account) {
    $this->getDecorated()->setAccount($account);
  }

  /**
   * @inheritDoc
   */
  public function getAccount() {
    return $this->getDecorated()->getAccount();
  }

  /**
   * @inheritDoc
   */
  public function id() {
    return $this->getDecorated()->id();
  }

  /**
   * @inheritDoc
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    return $this->getDecorated()->getRoles($exclude_locked_roles);
  }

  /**
   * @inheritDoc
   */
  public function hasPermission($permission) {
    return $this->getDecorated()->hasPermission($permission);
  }

  /**
   * @inheritDoc
   */
  public function isAuthenticated() {
    return $this->getDecorated()->isAuthenticated();
  }

  /**
   * @inheritDoc
   */
  public function isAnonymous() {
    return $this->getDecorated()->isAnonymous();
  }

  /**
   * @inheritDoc
   */
  public function getPreferredLangcode($fallback_to_default = TRUE) {
    return $this->getDecorated()->getPreferredLangcode($fallback_to_default);
  }

  /**
   * @inheritDoc
   */
  public function getPreferredAdminLangcode($fallback_to_default = TRUE) {
    return $this->getDecorated()->getPreferredAdminLangcode($fallback_to_default);
  }

  /**
   * @inheritDoc
   */
  public function getUsername() {
    return $this->getDecorated()->getUsername();
  }

  /**
   * @inheritDoc
   */
  public function getAccountName() {
    return $this->getDecorated()->getAccountName();
  }

  /**
   * @inheritDoc
   */
  public function getDisplayName() {
    return $this->getDecorated()->getDisplayName();
  }

  /**
   * @inheritDoc
   */
  public function getEmail() {
    return $this->getDecorated()->getEmail();
  }

  /**
   * @inheritDoc
   */
  public function getTimeZone() {
    return $this->getDecorated()->getTimeZone();
  }

  /**
   * @inheritDoc
   */
  public function getLastAccessedTime() {
    return $this->getDecorated()->getLastAccessedTime();
  }

  /**
   * @inheritDoc
   */
  public function setInitialAccountId($account_id) {
    $this->getDecorated()->setInitialAccountId($account_id);
  }

}
