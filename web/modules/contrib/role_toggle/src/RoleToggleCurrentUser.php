<?php

namespace Drupal\role_toggle;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

class RoleToggleCurrentUser extends AccountProxyDecoratorBase {

  /**
   * Prepare the user account and alter roles.
   *
   * Ensure this is done before any code can query roles.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  protected function prepareAccount(AccountInterface $account) {
    if ($account instanceof UserInterface && empty($account->role_toggle_account_prepared)) {
      RoleToggle::applyQueryCode($account);
      $account->role_toggle_account_prepared = TRUE;
    }
  }

  /**
   * @inheritDoc
   */
  public function getAccount() {
    $account = parent::getAccount();
    $this->prepareAccount($account);
    return $account;
  }

  /**
   * @inheritDoc
   */
  public function setAccount(AccountInterface $account) {
    $this->prepareAccount($account);
    parent::setAccount($account);
  }

  /**
   * @inheritDoc
   */
  public function id() {
    $this->getAccount();
    return parent::id();
  }

  /**
   * @inheritDoc
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    $this->getAccount();
    return parent::getRoles($exclude_locked_roles);
  }

  /**
   * @inheritDoc
   */
  public function hasPermission($permission) {
    $this->getAccount();
    return parent::hasPermission($permission);
  }

}
