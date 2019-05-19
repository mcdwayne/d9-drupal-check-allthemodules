<?php

namespace Drupal\sparkpost_requeue;

use Drupal\sparkpost\ClientServiceInterface;
use Drupal\sparkpost\MessageWrapper;
use Drupal\sparkpost\MessageWrapperInterface;

/**
 * Queued message wrapper class.
 */
class QueuedMessageWrapper extends MessageWrapper implements MessageWrapperInterface {

  /**
   * The number of tries we have had with this one.
   *
   * @var int
   */
  protected $retryCount = 0;

  /**
   * Last retry timestamp.
   *
   * @var int
   */
  protected $lastRetry = 0;

  /**
   * Client to use.
   *
   * @var \Drupal\sparkpost\ClientServiceInterface
   */
  protected $clientService;

  /**
   * QueuedMessageWrapper constructor.
   */
  public function __construct(ClientServiceInterface $clientService) {
    $this->clientService = $clientService;
    if ($this->getResult()) {
      $this->setResult($this->getResult());
    }
    if ($this->getApiResponseException()) {
      $this->setApiResponseException($this->getApiResponseException());
    }
  }

  /**
   * Get the count of retries.
   *
   * @return int
   *   The number of retries we have had.
   */
  public function getRetryCount() {
    return $this->retryCount;
  }

  /**
   * Increment the retry count.
   */
  public function incrementRetryCount() {
    $this->retryCount++;
  }

  /**
   * Gets the last retry.
   *
   * @return int
   *   The last retry timestamp.
   */
  public function getLastRetry() {
    return $this->lastRetry;
  }

  /**
   * Sets the last retry timestamp.
   *
   * @param int $lastRetry
   *   A timestamp.
   */
  public function setLastRetry($lastRetry) {
    $this->lastRetry = $lastRetry;
  }

}
