<?php

/**
 * @file
 * Contains \Drupal\apiservices\Exception\EndpointException.
 */

namespace Drupal\apiservices\Exception;

use Drupal\apiservices\ApiResponseInterface;

/**
 * An exception thrown when an API endpoint response contains an error
 * condition.
 */
class EndpointException extends ApiServiceException {

  /**
   * The API response that caused this exception.
   *
   * @var \Drupal\apiservices\ApiResponseInterface
   */
  protected $response;

  /**
   * Constructs an EndpointException object.
   *
   * @param string $message
   *   A description of the exception cause.
   * @param \Drupal\apiservices\ApiResponseInterface $response
   *   (optional) The API response that caused this exception. Defaults to NULL.
   * @param \Exception $previous
   *   (optional) The previously thrown exception, if this exception is part of
   *   a chain. Defaults to NULL.
   */
  public function __construct($message, ApiResponseInterface $response = NULL, \Exception $previous = NULL) {
    $code = isset($response) ? $response->getStatusCode() : 0;
    parent::__construct($message, $code, $previous);
    $this->response = $response;
  }

  /**
   * Gets the API response that caused this exception.
   *
   * @return \Drupal\apiservices\ApiResponseInterface
   *   The API response.
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Determines if this exception also contains an API response.
   *
   * @return bool
   *   If the exception has an API response, TRUE, otherwise FALSE.
   */
  public function hasResponse() {
    return !is_null($this->response);
  }

}
