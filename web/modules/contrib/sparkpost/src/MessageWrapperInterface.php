<?php

namespace Drupal\sparkpost;

/**
 * @file
 * Message class.
 */
use SparkPost\SparkPostException;

/**
 * Interface MessageWrapperInterface.
 */
interface MessageWrapperInterface {

  /**
   * MessageWrapperInterface constructor.
   *
   * @param \Drupal\sparkpost\ClientServiceInterface $clientService
   *   Client service.
   */
  public function __construct(ClientServiceInterface $clientService);

  /**
   * Gets the sparkpost message to send.
   *
   * @return array
   *   Message structure,
   */
  public function getSparkpostMessage();

  /**
   * Gets the Drupal message the sparkpost message was based on.
   *
   * @return array
   *   The drupal message.
   */
  public function getDrupalMessage();

  /**
   * Gets the last exception thrown.
   *
   * @return \SparkPost\SparkPostException
   *   The exception.
   */
  public function getApiResponseException();

  /**
   * Gets the last result.
   *
   * @return array
   *   Result.
   */
  public function getResult();

  /**
   * Gets the client service.
   */
  public function getClientService();

  /**
   * Sets the sparkpost message.
   *
   * @param array $sparkpost_message
   *   The message structure needed.
   */
  public function setSparkpostMessage(array $sparkpost_message);

  /**
   * Sets the Drupal message.
   *
   * @param array $drupal_message
   *   Drupal message structure.
   */
  public function setDrupalMessage(array $drupal_message);

  /**
   * Sets the result from the API call.
   *
   * @param array $result
   *   The result we got.
   */
  public function setResult(array $result);

  /**
   * Sets the API response exception.
   *
   * @param \SparkPost\SparkPostException $sparkPostException
   *   The exception.
   */
  public function setApiResponseException(SparkPostException $sparkPostException);

  /**
   * Clears the API response exception.
   */
  public function clearApiResposeException();

  /**
   * Sends the message.
   *
   * @return bool
   *   If it was a success or not.
   */
  public function sendMessage();

}
