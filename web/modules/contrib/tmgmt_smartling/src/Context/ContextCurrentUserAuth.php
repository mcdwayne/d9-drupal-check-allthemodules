<?php

namespace Drupal\tmgmt_smartling\Context;

use Drupal\Core\Session\AccountInterface;
use Drupal\tmgmt_smartling\Exceptions\WrongUsernameException;

class ContextCurrentUserAuth {

  /**
   * @var AccountInterface
   */
  protected $currentAccount;

  /**
   * ContextCurrentUserAuth constructor.
   * @param AccountInterface $account
   */
  public function __construct(AccountInterface $account) {
    $this->currentAccount = $account;
  }

  /**
   * Returns cookies of the needed user.
   *
   * @param string $name
   * @return string
   * @throws WrongUsernameException
   */
  public function getCookies($name) {
    if ($this->currentAccount->getAccountName() !== $name) {
      throw new WrongUsernameException('You tried to authenticate with a username that is different from the current user. This feature wasn\'t implemented yet.');
    }
    return session_name() . "=" . session_id();
  }

  public function getCurrentAccount() {
    return $this->currentAccount;
  }

}
