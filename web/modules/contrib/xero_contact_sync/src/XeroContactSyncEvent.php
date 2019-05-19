<?php

namespace Drupal\xero_contact_sync;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Provides a xero contact sync event for event listeners.
 */
class XeroContactSyncEvent extends Event {

  /**
   * Data.
   *
   * @var array
   */
  protected $data;

  /**
   * The user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Constructs a xero contact sync event object.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param array &$data
   *   The data.
   */
  public function __construct(UserInterface $user, array &$data) {
    $this->user = $user;
    $this->data = &$data;
  }

  /**
   * Gets the data.
   *
   * @return array
   *   The data.
   */
  public function getData() {
    return $this->data;
  }

  public function setData(array $data) {
    $this->data = $data;
  }

  /**
   * Gets the user.
   *
   * @return \Drupal\user\UserInterface
   *   The user.
   */
  public function getUser() {
    return $this->user;
  }

}
