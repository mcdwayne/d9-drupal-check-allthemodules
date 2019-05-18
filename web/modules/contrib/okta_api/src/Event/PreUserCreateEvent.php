<?php

namespace Drupal\okta_api\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class PreUserCreateEvent.
 *
 * @package Drupal\okta_api\Event
 */
class PreUserCreateEvent extends Event {

  const OKTA_API_PREUSERCREATE = 'okta_api.preusercreate';

  protected $user;

  /**
   * PreUserCreateEvent constructor.
   *
   * @param array $user
   *   User.
   */
  public function __construct(array $user) {
    $this->user = $user;
  }

  /**
   * Getter for the user array.
   *
   * @return array
   *   User
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Setter for user array.
   *
   * @param array $user
   *   User.
   */
  public function setUser(array $user) {
    $this->user = $user;
  }

}
