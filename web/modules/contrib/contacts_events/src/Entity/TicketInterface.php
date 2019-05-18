<?php

namespace Drupal\contacts_events\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ticket entities.
 *
 * @ingroup contacts_events
 */
interface TicketInterface extends SingleUsePurchasableEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Get the booking the ticket belongs to.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The order this ticket belongs to.
   */
  public function getBooking();

  /**
   * Get the event the ticket is for.
   *
   * @return \Drupal\contacts_events\Entity\EventInterface|null
   *   The event this ticket is for.
   */
  public function getEvent();

  /**
   * Get the formatted name.
   */
  public function getName();

  /**
   * Gets the Ticket creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ticket.
   */
  public function getCreatedTime();

  /**
   * Sets the Ticket creation timestamp.
   *
   * @param int $timestamp
   *   The Ticket creation timestamp.
   *
   * @return \Drupal\contacts_events\Entity\TicketInterface
   *   The called Ticket entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Performs acquisitions on this ticket using the email address.
   *
   * @param bool $early
   *   Whether this is an early acquisition. For early acquisitions, we don't
   *   create a user, save anything or update profiles.
   */
  public function acquire($early = FALSE);

}
