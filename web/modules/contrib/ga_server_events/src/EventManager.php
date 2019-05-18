<?php

namespace Drupal\ga_server_events;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;

/**
 * EventManager service.
 */
class EventManager {

  /**
   * @var StateInterface
   */
  protected $state;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs an EventManager object.
   *
   * @param StateInterface $state
   *   The state manager thingy.
   */
  public function __construct(StateInterface $state, AccountInterface $current_user) {
    $this->state = $state;
    $this->currentUser = $current_user;
  }

  /**
   * Adds an event.
   *
   * Will always be for the current user.
   */
  public function addEvent(Event $event) {
    $this->addUserEvent($this->currentUser, $event);
  }

  public function addUserEvent(AccountInterface $user, $event) {
    $key = $this->createUserKey($user);
    $events = $this->state->get($key, []);
    $events[] = $event;
    $this->state->set($key, $events);
  }

  protected function createUserKey(AccountInterface $user) {
    return 'ga_server_events_' . $user->id();
  }

  /**
   * Retrieves all events.
   *
   * @return \Drupal\ga_server_events\Event[]
   *   An array of events for the current user.
   */
  public function getEvents() {
    return $this->getUserEvents($this->currentUser);
  }

  /**
   * Get the user events.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user you want to check.
   *
   * @return \Drupal\ga_server_events\Event[]
   *   An array of events for the specified user.
   */
  public function getUserEvents(AccountInterface $user) {
    return $this->state->get('ga_server_events_' . $user->id(), []);
  }

  /**
   * Clears the events for the current user.
   */
  public function clearEvents() {
    $this->clearUserEvents($this->currentUser);
  }

  /**
   * Clears the events for the specified user.
   */
  public function clearUserEvents(AccountInterface $user) {
    $key = $this->createUserKey($user);
    $this->state->set($key, []);
    $this->state->resetCache();
  }

}
