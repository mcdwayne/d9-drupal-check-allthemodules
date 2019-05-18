<?php

namespace Drupal\role_expire\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a user loses one of its roles.
 *
 * @see role_expire_cron()
 */
class RoleExpiresEvent extends Event {

  const EVENT_NAME = 'role_expire_event_role_expires';

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  public $account;

  /**
   * The rid of the role which the user has lost.
   */
  public $ridBefore;

  /**
   * Constructs the object.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account of the user logged in.
   *
   * @param $rid
   *   The role name that has expired.
   */
  public function __construct(UserInterface $account, $ridBefore) {
    $this->account = $account;
    $this->ridBefore = $ridBefore;
  }

}
