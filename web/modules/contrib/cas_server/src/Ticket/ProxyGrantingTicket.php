<?php

/**
 * @file
 * Contains \Drupal\cas_server\Ticket\ProxyGrantingTicket
 */

namespace Drupal\cas_server\Ticket;

class ProxyGrantingTicket extends Ticket {
  
  /**
   * @var array
   *
   * The chain of pgtUrls used to generate this ticket.
   */
  protected $proxyChain;

  /**
   * Constructor.
   *
   * @param string $ticket_id
   *   The ticket id.
   * @param string $timestamp
   *   The expiration time of the ticket.
   * @param string $session_id
   *   The hashed session id.
   * @param string $uid
   *   The uid of the requestor.
   * @param string $username
   *   The username of requestor.
   * @param array $proxy_chain
   *   The array of pgturls used to generate this pgt. 
   */
  public function __construct($ticket_id, $timestamp, $session_id, $uid, $username, $proxy_chain) {
    $this->id = $ticket_id;
    $this->expirationTime = $timestamp;
    $this->session = $session_id;
    $this->uid = $uid;
    $this->user = $username;
    $this->proxyChain = $proxy_chain;
  }

  public function getProxyChain() {
    return $this->proxyChain;
  }
}
