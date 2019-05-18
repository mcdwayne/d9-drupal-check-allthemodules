<?php

namespace Drupal\discourse_sync;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event class to be dispatched from the discourse_sso controller.
 */
class UserEvent extends Event {

  const EVENT = 'discourse_sync.user';
  
  protected $user;

  public function __construct($user) {
    $this->user = $user;
  }

  public function getUid() {
    return $this->user->id();
  }
  
  public function getUsername() {
    return $this->user->getUsername();
  }
  
  public function getUserRoles() {
    return $this->user->getRoles();
  }
}