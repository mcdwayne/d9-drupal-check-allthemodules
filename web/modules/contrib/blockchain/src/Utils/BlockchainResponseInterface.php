<?php

namespace Drupal\blockchain\Utils;

/**
 * Class BlockchainResponseInterface.
 *
 * @package Drupal\blockchain\Utils
 */
interface BlockchainResponseInterface extends BlockchainHttpInterface {

  const PARAM_MESSAGE = 'message';
  const PARAM_DETAILS = 'details';

  /**
   * Getter for status code.
   *
   * @return string
   *   Value.
   */
  public function getStatusCode();

  /**
   * Setter for status code.
   *
   * @param string $statusCode
   *   Value.
   *
   * @return $this
   *   Chaining.
   */
  public function setStatusCode($statusCode);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getMessageParam();

  /**
   * Getter for param.
   *
   * @param string $message
   *   Param value.
   *
   * @return $this
   *   Chaining.
   */
  public function setMessageParam($message);

  /**
   * Property getter.
   *
   * @return string
   *   Value.
   */
  public function getDetailsParam();

  /**
   * Getter for param.
   *
   * @param string $details
   *   Param value.
   *
   * @return $this
   *   Chaining.
   */
  public function setDetailsParam($details);

  /**
   * Returns prepared json response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response object.
   */
  public function toJsonResponse();

  /**
   * Predicate to define if response is OK (200).
   *
   * @return bool
   *   Test result.
   */
  public function isStatusOk();

  /**
   * Factory method.
   *
   * @return $this
   *   Chaining.
   */
  public static function create();

  /**
   * Handles logging.
   *
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger for the given channel.
   *
   * @return $this
   *   Chaining.
   */
  public function log($logger);

  /**
   * Setter for array of params.
   *
   * @param array $params
   *   Array of params.
   *
   * @return $this
   *   Chaining.
   */
  public function setParams(array $params);

}
