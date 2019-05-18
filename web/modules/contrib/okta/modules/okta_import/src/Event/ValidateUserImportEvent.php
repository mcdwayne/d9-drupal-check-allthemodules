<?php

namespace Drupal\okta_import\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ValidateUserImportEvent.
 *
 * @package Drupal\okta_import\Event
 */
class ValidateUserImportEvent extends Event {

  const OKTA_IMPORT_VALIDATEUSERIMPORT = 'okta_import.validateuserimport';

  protected $emails;

  /**
   * ValidateUserImportEvent constructor.
   *
   * @param array $emails
   *   Email Addresses.
   */
  public function __construct(array $emails) {
    $this->emails = $emails;
  }

  /**
   * Getter for the emails array.
   *
   * @return emails
   *   Email Addresses.
   */
  public function getEmails() {
    return $this->emails;
  }

  /**
   * Setter for emails array.
   *
   * @param array $emails
   *   Email Addresses.
   */
  public function setEmails(array $emails) {
    $this->emails = $emails;
  }

}
