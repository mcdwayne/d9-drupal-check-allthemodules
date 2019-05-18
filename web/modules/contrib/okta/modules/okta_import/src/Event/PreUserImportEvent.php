<?php

namespace Drupal\okta_import\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class PreUserImportEvent.
 *
 * @package Drupal\okta_import\Event
 */
class PreUserImportEvent extends Event {

  const OKTA_IMPORT_PREUSERIMPORT = 'okta_import.preuserimport';

  protected $user;

  /**
   * PreUserImportEvent constructor.
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
