<?php

namespace Drupal\entity_pilot_test;

use Drupal\Core\State\StateInterface;
use Drupal\entity_pilot\AccountInterface;
use Drupal\entity_pilot\Data\FlightManifestInterface;
use Drupal\entity_pilot\Exception\TransportException;
use Drupal\entity_pilot\MockTransportInterface;
use Drupal\entity_pilot\Transport;

/**
 * Defines a mock transport handler.
 */
class MockTransport extends Transport implements MockTransportInterface {

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new mock transport.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function sendFlight(FlightManifestInterface $manifest, $secret) {
    $this->sentFlight = $manifest;
    $this->state->set('entity_pilot_test_transport_sent_flight', $manifest);
    if ($e = $this->getExceptionReturn()) {
      $this->setExceptionReturn(NULL);
      throw new TransportException($e);
    }
    return $this->getSendReturn();
  }

  /**
   * {@inheritdoc}
   */
  public function queryFlights(AccountInterface $account, $search_string = '', $limit = 50, $offset = 0) {
    if ($e = $this->getExceptionReturn()) {
      $this->setExceptionReturn(NULL);
      throw new TransportException($e);
    }
    return $this->getQueryReturn();
  }

  /**
   * {@inheritdoc}
   */
  public function getFlight($remote_id, AccountInterface $account) {
    if ($e = $this->getExceptionReturn()) {
      $this->setExceptionReturn(NULL);
      throw new TransportException($e);
    }
    $return = $this->getQueryReturn();
    return $return[$remote_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getSendReturn() {
    return $this->state->get('entity_pilot_test_transport_send_return');
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryReturn() {
    return $this->state->get('entity_pilot_test_transport_query');
  }

  /**
   * {@inheritdoc}
   */
  public function getExceptionReturn() {
    return $this->state->get('entity_pilot_test_transport_exception');
  }

  /**
   * {@inheritdoc}
   */
  public function setSendReturn($remote_id) {
    $this->state->set('entity_pilot_test_transport_send_return', $remote_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setExceptionReturn($message = NULL) {
    $this->state->set('entity_pilot_test_transport_exception', $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryReturn(array $flights) {
    $this->state->set('entity_pilot_test_transport_query', $flights);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSentFlight() {
    return $this->state->get('entity_pilot_test_transport_sent_flight');
  }

}
