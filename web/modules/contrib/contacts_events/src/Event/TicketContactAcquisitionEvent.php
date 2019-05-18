<?php

namespace Drupal\contacts_events\Event;

use Drupal\contacts_events\Entity\Ticket;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event raised when a ticket is attached to a contact via acquisitions.
 *
 * @package Drupal\contacts_events\Event
 */
class TicketContactAcquisitionEvent extends Event {
  const NAME = 'contats_events.ticket.acquire';

  /**
   * The ticket.
   *
   * @var \Drupal\contacts_events\Entity\Ticket
   */
  public $ticket;

  /**
   * User that was attached to the ticket via acquisitions.
   *
   * @var \Drupal\user\Entity\User
   */
  public $user;

  /**
   * The method of acquisition (update/create).
   *
   * @var string
   */
  public $acquisitionMethod;


  /**
   * Profiles that were created during the acquisition process.
   *
   * @var \Drupal\Core\Entity\Entity[]
   */
  public $entitiesToSave = [];

  /**
   * TicketContactAcquisitionEvent constructor.
   *
   * @param \Drupal\contacts_events\Entity\Ticket $ticket
   *   The ticket that was updated.
   * @param \Drupal\user\Entity\User $user
   *   The user that was acquired.
   * @param string $acquisition_method
   *   Acquisition method (create/update)
   */
  public function __construct(Ticket $ticket, User $user, $acquisition_method) {
    $this->ticket = $ticket;
    $this->user = $user;
    $this->acquisitionMethod = $acquisition_method;
  }

}
