<?php

namespace Drupal\sparkpost;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use SparkPost\SparkPostException;

/**
 * Message wrapper class.
 */
class MessageWrapper implements MessageWrapperInterface {
  use DependencySerializationTrait;

  /**
   * The sparkpost message.
   *
   * @var array
   */
  protected $sparkpostMessage;

  /**
   * The Drupal message.
   *
   * @var array
   */
  protected $drupalMessage;

  /**
   * Exception, if any.
   *
   * @var \SparkPost\SparkPostException
   */
  protected $apiResponseException;

  /**
   * Result, if any.
   *
   * @var array
   */
  protected $result;

  /**
   * Client to use.
   *
   * @var \Drupal\sparkpost\ClientServiceInterface
   */
  protected $clientService;

  /**
   * SparkpostMessage constructor.
   */
  public function __construct(ClientServiceInterface $clientService) {
    $this->clientService = $clientService;
  }

  /**
   * {@inheritdoc}
   */
  public function setDrupalMessage(array $drupal_message) {
    $this->drupalMessage = $drupal_message;
  }

  /**
   * {@inheritdoc}
   */
  public function setSparkpostMessage(array $sparkpost_message) {
    $this->sparkpostMessage = $sparkpost_message;
  }

  /**
   * {@inheritdoc}
   */
  public function setApiResponseException(SparkPostException $sparkPostException) {
    $this->apiResponseException = $sparkPostException;
  }

  /**
   * {@inheritdoc}
   */
  public function setResult(array $result) {
    $this->result = $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSparkpostMessage() {
    return $this->sparkpostMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalMessage() {
    return $this->drupalMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiResponseException() {
    return $this->apiResponseException;
  }

  /**
   * {@inheritdoc}
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientService() {
    return $this->clientService;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage() {
    try {
      $data = $this->clientService->sendMessage($this->sparkpostMessage);
      $this->setResult($data);
      \Drupal::moduleHandler()->invokeAll('sparkpost_mailsend_success', [$this]);
      return TRUE;
    }
    catch (SparkPostException $e) {
      $this->setApiResponseException($e);
    }
    catch (\Exception $e) {
      // @todo: Handle sparkpost exceptions separately.
    }
    \Drupal::moduleHandler()->invokeAll('sparkpost_mailsend_error', [$this]);
    return FALSE;
  }

  /**
   * Clears the API response exception.
   */
  public function clearApiResposeException() {
    $this->apiResponseException = NULL;
  }

}
