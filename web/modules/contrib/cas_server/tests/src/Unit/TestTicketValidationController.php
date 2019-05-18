<?php

/**
 * @file
 * Contains \Drupal\Test\cas_server\Unit\TestTicketValidationController.
 */

namespace Drupal\Tests\cas_server\Unit;

use Drupal\cas_server\Controller\TicketValidationController;
use GuzzleHttp\Client;
use Drupal\cas_server\Ticket\TicketFactory;
use Drupal\cas_server\Ticket\TicketStorageInterface;
use Drupal\cas_server\Ticket\Ticket;

/**
 * Provide a way to unit test the proxy callback procedure with minimal
 * infrastructure.
 */
class TestTicketValidationController extends TicketValidationController {

  /**
   * Overwrite the controller, we only need the http client, a ticket factory,
   * and a ticket store.
   */
  public function __construct(Client $http_client, TicketFactory $ticket_factory, TicketStorageInterface $ticket_store) {
    $this->httpClient = $http_client;
    $this->ticketFactory = $ticket_factory;
    $this->ticketStore = $ticket_store;
  }

  /**
   * Simply call the proxyCallback function (can't test directly because it is
   * a protected function.
   */
  public function callProxyCallback($pgtUrl, Ticket $ticket) {
    return $this->proxyCallback($pgtUrl, $ticket);
  }

}
