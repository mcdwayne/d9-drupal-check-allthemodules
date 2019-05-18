<?php

namespace Drupal\invite;

use Symfony\Component\EventDispatcher\Event;

/**
 * Invite Accept class.
 *
 * @codingStandardsIgnoreStart
 */
class InviteAcceptEvent extends Event {
  protected $invite_accept;
  
  /**
   * Construct.
   *
   * @codingStandardsIgnoreEnd
   */
  public function __construct($invite_accept) {
    $this->invite_accept = $invite_accept;
  }

  /**
   * Function to get Invite.
   */
  public function getInviteAcceptEvent() {
    return $this->invite_accept;
  }

  /**
   * Function to set Invite..
   */
  public function setInviteAcceptEvent($invite_accept) {
    $this->invite_accept = $invite_accept;
  }

}
