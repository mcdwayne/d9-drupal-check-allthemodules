<?php

namespace Drupal\okta_import\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class PostUserImportEvent.
 *
 * @package Drupal\okta_import\Event
 */
class PostUserImportEvent extends Event {

  const OKTA_IMPORT_POSTUSERIMPORT = 'okta_import.postuserimport';

  protected $user;

  /**
   * PostUserImportEvent constructor.
   *
   * @param object $user
   *   User.
   */
  public function __construct($user) {
    $this->user = $user;
  }

  /**
   * Getter for the user array.
   *
   * @return user
   *   User
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Setter for user array.
   *
   * @param object $user
   *   User.
   */
  public function setUser($user) {
    $this->user = $user;
  }

}
