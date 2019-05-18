<?php

/**
 * @file
 * Contains \Drupal\cas_server\Ticket\Ticket
 */

namespace Drupal\cas_server\Ticket;

abstract class Ticket {

  /**
   * @var string
   *
   * The ticket identifier string.
   */
  protected $id;

  /**
   * @var string
   *
   * A unix timestamp representing the expiration time of the ticket.
   */
  protected $expirationTime;

  /**
   * @var string
   *
   * A hashed session ID for the session that requested ticket.
   */
  protected $session;

  /**
   * @var int
   *
   * The uid of the user who requested the ticket.
   */
  protected $uid;

  /**
   * @var string
   *
   * The username of the user who requested the ticket.
   */
  protected $user;

  /**
   * Constructor.
   *
   * @param string $ticket_id
   *   The ticket id.
   * @param string $timestamp
   *   The expiration time of the ticket.
   * @param string $session_id
   *   The hashed session id.
   * @param string $username
   *   The username of requestor.
   */
  public function __construct($ticket_id, $timestamp, $session_id, $uid, $username) {
    $this->id = $ticket_id;
    $this->expirationTime = $timestamp;
    $this->session = $session_id;
    $this->uid = $uid;
    $this->user = $username;
  }

  /**
   * Return the user.
   *
   * @return string
   *   The user property.
   */
  public function getUid() {
    return $this->uid;
  }

  /**
   * Return the user.
   *
   * @return string
   *   The user property.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Set the username for this ticket.
   *
   * @param string $username
   *   The username to be set.
   */
  public function setUser($username) {
    $this->user = $username;
  }

  /**
   * Return the id of the ticket.
   *
   * @return string
   *   The id property.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Return the expiration time.
   *
   * @return string
   *   The expiration time.
   */
  public function getExpirationTime() {
    return $this->expirationTime;
  }

  /**
   * Return the session.
   *
   * @return string
   *   The session.
   */
  public function getSession() {
    return $this->session;
  }

}
