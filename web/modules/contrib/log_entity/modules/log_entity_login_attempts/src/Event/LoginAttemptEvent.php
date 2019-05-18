<?php

namespace Drupal\log_entity_login_attempts\Event;

use Drupal\log_entity\Event\LogEvent;

/**
 * The login attempt event.
 */
class LoginAttemptEvent extends LogEvent {

  /**
   * Whether or not the event was successful.
   *
   * @var bool
   */
  protected $wasSuccessful;

  /**
   * The account name.
   *
   * @var string
   */
  protected $username;

  /**
   * LoginAttemptEvent constructor.
   *
   * @param string $event_type
   *   The event type.
   * @param string $username
   *   The username.
   * @param bool $wasSuccessful
   *   Whether login succeeded or not.
   */
  public function __construct($event_type, $username, $wasSuccessful) {
    parent::__construct($event_type);
    $this->username = $username;
    $this->wasSuccessful = $wasSuccessful;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return sprintf('User %s %s.', $this->username, $this->wasSuccessful ? 'successfully logged in' : 'failed to login');
  }

}
