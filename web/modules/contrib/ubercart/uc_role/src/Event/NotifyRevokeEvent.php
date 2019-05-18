<?php

namespace Drupal\uc_role\Event;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a user role is revoked.
 */
class NotifyRevokeEvent extends Event {

  const EVENT_NAME = 'uc_role_notify_revoke';

  /**
   * The user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public $account;


  /**
   * The expiration.
   *
   * @var array
   */
  public $expiration;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user.
   * @param array $expiration
   *   The expiration.
   */
  public function __construct(AccountInterface $account, array $expiration) {
    $this->account = $account;
    $this->expiration = $expiration;
  }

}
