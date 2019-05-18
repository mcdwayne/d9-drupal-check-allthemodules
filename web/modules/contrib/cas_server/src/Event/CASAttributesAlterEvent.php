<?php

namespace Drupal\cas_server\Event;


use Drupal\cas_server\Ticket\ServiceTicket;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

class CASAttributesAlterEvent extends Event {

  const CAS_ATTRIBUTES_ALTER_EVENT = 'cas.attributes.alter';

  /**
   * @var
   */
  protected $attributes;

  protected $ticket;

  protected $user;

  /**
   * CASAttributesAlterEvent constructor.
   *
   * @param UserInterface $user
   * @param ServiceTicket $ticket
   */
  public function __construct(UserInterface $user, ServiceTicket $ticket) {
    $this->user = $user;
    $this->ticket = $ticket;
  }

  /**
   * @return ServiceTicket
   */
  public function getTicket() {
    return $this->ticket;
  }

  /**
   * @return UserInterface
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * @return mixed
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * @param $attributes
   */
  public function setAttributes($attributes) {
    $this->attributes = $attributes;
  }

}
