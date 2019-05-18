<?php

namespace Drupal\jsonrpc\Exception;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\jsonrpc\Object\Error;
use Drupal\jsonrpc\Object\Response;

/**
 * Custom exception class for the module.
 */
class JsonRpcException extends \Exception implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * The JSON-RPC error response for the exception.
   *
   * @var \Drupal\jsonrpc\Object\Response
   *   The RPC response object.
   */
  protected $response;

  /**
   * JsonRpcException constructor.
   *
   * @param \Drupal\jsonrpc\Object\Response $response
   *   The JSON-RPC error response object for the exception.
   * @param \Throwable $previous
   *   The previous exception.
   */
  public function __construct(Response $response, \Throwable $previous = NULL) {
    $this->response = $response;
    $error = $response->getError();
    $this->setCacheability($response);
    parent::__construct($error->getMessage(), $error->getCode(), $previous);
  }

  /**
   * The appropriate JSON-RPC error response for the exception.
   *
   * @return \Drupal\jsonrpc\Object\Response
   *   The RPC response object.
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Constructs a JsonRpcException from an arbitrary exception.
   *
   * @param \Throwable|\Exception $previous
   *   An arbitrary exception.
   * @param mixed $id
   *   The request ID, if available.
   * @param string $version
   *   (optional) The JSON-RPC version.
   *
   * @return static
   */
  public static function fromPrevious($previous, $id = FALSE, $version = NULL) {
    if ($previous instanceof JsonRpcException) {
      // Ensures that the ID and version context information are set because it
      // might not have been set or accessible at a lower level.
      $response = $previous->getResponse();
      return static::fromError($response->getError(), $response->id() ?: $id, $response->version());
    }
    $error = Error::internalError($previous->getMessage());
    $response = static::buildResponse($error, $id, $version);
    return new static($response, $previous);
  }

  /**
   * Constructs a JsonRpcException from an arbitrary error object.
   *
   * @param \Drupal\jsonrpc\Object\Error $error
   *   The error which caused the exception.
   * @param mixed $id
   *   The request ID, if available.
   * @param string $version
   *   (optional) The JSON-RPC version.
   *
   * @return static
   */
  public static function fromError(Error $error, $id = FALSE, $version = NULL) {
    return new static(static::buildResponse($error, $id, $version));
  }

  /**
   * Helper to build a JSON-RPC response object.
   *
   * @param \Drupal\jsonrpc\Object\Error $error
   *   The error object.
   * @param mixed $id
   *   The request ID.
   * @param string $version
   *   The information version.
   *
   * @return \Drupal\jsonrpc\Object\Response
   *   The RPC response object.
   */
  protected static function buildResponse(Error $error, $id = FALSE, $version = NULL) {
    $supported_version = $version ?: \Drupal::service('jsonrpc.handler')->supportedVersion();
    return new Response($supported_version, $id ? $id : NULL, NULL, $error);
  }

}
