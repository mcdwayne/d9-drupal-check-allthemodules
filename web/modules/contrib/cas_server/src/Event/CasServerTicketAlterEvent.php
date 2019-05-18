<?php

namespace Drupal\cas_server\Event;


use Drupal\cas_server\Ticket\Ticket;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

class CasServerTicketAlterEvent extends Event {

  const CAS_SERVER_TICKET_ALTER_EVENT = 'cas_server.ticket.alter';

  protected $ticket;

  /**
   * CasServerTicketAlterEvent constructor.
   *
   * @param UserInterface $user
   * @param Ticket $ticket
   */
  public function __construct(Ticket $ticket) {
    $this->ticket = $ticket;
  }

  /**
   * @return Ticket
   */
  public function getTicket() {
    return $this->ticket;
  }

}
