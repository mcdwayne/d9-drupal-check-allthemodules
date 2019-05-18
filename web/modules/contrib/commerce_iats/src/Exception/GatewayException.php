<?php

namespace Drupal\commerce_iats\Exception;

use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GatewayException.
 */
class GatewayException extends BadResponseException {

  /**
   * Response data.
   *
   * @var array
   */
  protected $data;

  /**
   * {@inheritdoc}
   */
  public function __construct($message, RequestInterface $request, ResponseInterface $response = NULL, \Exception $previous = NULL, array $handlerContext = []) {
    parent::__construct($message, $request, $response, $previous, $handlerContext);
    $this->data = ($response !== NULL) ? json_decode($response->getBody()) : NULL;
  }

  /**
   * Gets the exception data.
   *
   * @return array
   *   The exception data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Creates a GatewayException from a BadResponseException.
   *
   * @param \GuzzleHttp\Exception\BadResponseException $e
   *   The BadResponseException exception.
   *
   * @return static
   *   The GatewayException.
   */
  public static function createFromBadResponse(BadResponseException $e) {
    return new static($e->getMessage(), $e->getRequest(), $e->getResponse(), $e->getPrevious(), $e->getHandlerContext());
  }

}
