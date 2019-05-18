<?php

namespace Drupal\jsonrpc\Object;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Response object to help implement JSON RPC's spec for response objects.
 */
class Response implements CacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The JSON-RPC version.
   *
   * @var string
   */
  protected $version;

  /**
   * A string, number or NULL ID.
   *
   * @var mixed
   */
  protected $id;

  /**
   * The result.
   *
   * @var mixed
   */
  protected $result;

  /**
   * The schema for the result.
   *
   * @var null|array
   */
  protected $resultSchema;

  /**
   * The error.
   *
   * @var \Drupal\jsonrpc\Object\Error
   */
  protected $error;

  /**
   * Response constructor.
   *
   * @param string $version
   *   The JSON-RPC version.
   * @param mixed $id
   *   The response ID. Must match the ID of the generating request.
   * @param mixed $result
   *   A result value. Must not be provided if an error is to be provided.
   * @param \Drupal\jsonrpc\Object\Error $error
   *   An error object if the response resulted in an error. Must not be
   *   provided if a result was provided.
   */
  public function __construct($version, $id, $result = NULL, Error $error = NULL) {
    $this->assertValidResponse($version, $id, $result, $error);
    $this->version = $version;
    $this->id = $id;
    if (!is_null($result)) {
      $this->result = $result;
    }
    else {
      $this->error = $error;
      $this->setCacheability($error);
    }
  }

  /**
   * Gets the ID.
   *
   * @return mixed
   *   The ID.
   */
  public function id() {
    return $this->id;
  }

  /**
   * Gets the version.
   *
   * @return string
   *   The version.
   */
  public function version() {
    return $this->version;
  }

  /**
   * Get the result of the response.
   *
   * @return mixed
   *   The result of the response.
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Get the error of the response.
   *
   * @return mixed
   *   The error of the response.
   */
  public function getError() {
    return $this->error;
  }

  /**
   * Checks if this is an error or result response.
   *
   * @return bool
   *   True if it's a result response.
   */
  public function isResultResponse() {
    return !$this->isErrorResponse();
  }

  /**
   * Checks if this is an error or result response.
   *
   * @return bool
   *   True if it's an error response.
   */
  public function isErrorResponse() {
    return isset($this->error);
  }

  /**
   * Asserts that the response is valid.
   */
  protected function assertValidResponse($version, $id, $result, $error) {
    assert(!is_null($result) xor !is_null($error), 'Either the result member or error member MUST be included, but both members MUST NOT be included.');
    assert($version === "2.0", 'A String specifying the version of the JSON-RPC protocol. MUST be exactly "2.0".');
    assert(is_string($id) || is_numeric($id) || is_null($id), 'An identifier established by the Client that MUST contain a String, Number, or NULL value if included.');
  }

  /**
   * The schema of the output response.
   *
   * @return array|null
   *   The result schema.
   */
  public function getResultSchema() {
    return $this->resultSchema;
  }

  /**
   * Sets the schema for the output response.
   *
   * @param array|null $result_schema
   *   The schema of the result.
   */
  public function setResultSchema($result_schema) {
    $this->resultSchema = $result_schema;
  }

}
