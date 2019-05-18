<?php

namespace Drupal\jsonrpc\Object;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Error class to help implement JSON RPC's spec for errors.
 */
class Error implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  const PARSE_ERROR = -32700;
  const INVALID_REQUEST = -32600;
  const METHOD_NOT_FOUND = -32601;
  const INVALID_PARAMS = -32602;
  const INTERNAL_ERROR = -32603;

  public static $errorMessages = [
    -32700 => 'Parse Error',
    -32600 => 'Invalid Request',
    -32601 => 'Method Not Found',
    -32602 => 'Invalid Params',
    -32603 => 'Internal Error',
  ];

  public static $errorMeanings = [
    -32700 => 'Invalid JSON was received by the server. An error occurred on the server while parsing the JSON text.',
    -32600 => 'The JSON sent is not a valid Request object.',
    -32601 => "The method '%s' does not exist/is not available.",
    -32602 => 'Invalid method parameter(s).',
    -32603 => 'Internal JSON-RPC error.',
  ];

  /**
   * The error's type code.
   *
   * @var int
   */
  protected $code;

  /**
   * The error's short description.
   *
   * @var string
   */
  protected $message;

  /**
   * Additional information about the error.
   *
   * @var mixed
   */
  protected $data;

  /**
   * Error constructor.
   *
   * @param int $code
   *   The error's type code.
   * @param string $message
   *   The error's short description.
   * @param mixed $data
   *   (optional) A primitive or structured value that contains additional
   *   information about the error. This may be omitted.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   (optional) A cacheable dependency.
   */
  public function __construct($code, $message, $data = NULL, CacheableDependencyInterface $cacheability = NULL) {
    $this->assertValidError($code, $message);
    $this->code = $code;
    $this->message = $message;
    if (!is_null($data)) {
      $this->data = $data;
    }
    $this->setCacheability($cacheability ?: new CacheableMetadata());
  }

  /**
   * Get the error's type code.
   *
   * @return int
   *   The error code.
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * Get the error's short description.
   *
   * @return string
   *   The error message.
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Get additional information about the error.
   *
   * @return mixed
   *   The additional data about the error.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Asserts that the error is valid.
   *
   * @param mixed $code
   *   The HTTP code.
   * @param mixed $message
   *   The output message.
   */
  protected function assertValidError($code, $message) {
    assert(is_int($code) && !($code >= -32000 && $code <= -32099), "The $code code is reserved for implementation-defined server-errors.");
    assert(is_string($message) && strlen($message) < 256, 'The message SHOULD be limited to a concise single sentence.');
  }

  /**
   * Constructs a new parse error.
   *
   * @param mixed $data
   *   More specific information about the error.
   *
   * @return static
   */
  public static function parseError($data = NULL) {
    return new static(static::PARSE_ERROR, static::$errorMessages[static::PARSE_ERROR], $data ?: static::$errorMeanings[static::PARSE_ERROR]);
  }

  /**
   * Constructs a new invalid request error.
   *
   * @param mixed $data
   *   More specific information about the error.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   (optional) A cacheable dependency.
   *
   * @return static
   */
  public static function invalidRequest($data = NULL, CacheableDependencyInterface $cacheability = NULL) {
    return new static(static::INVALID_REQUEST, static::$errorMessages[static::INVALID_REQUEST], $data ?: static::$errorMeanings[static::INVALID_REQUEST], $cacheability);
  }

  /**
   * Constructs a new method not found error.
   *
   * @param string $method_name
   *   The name of the missing method.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   (optional) A cacheable dependency.
   *
   * @return static
   */
  public static function methodNotFound($method_name, CacheableDependencyInterface $cacheability = NULL) {
    $data = sprintf(static::$errorMeanings[static::METHOD_NOT_FOUND], $method_name);
    return new static(static::METHOD_NOT_FOUND, static::$errorMessages[static::METHOD_NOT_FOUND], $data, $cacheability);
  }

  /**
   * Constructs a new invalid params error.
   *
   * @param mixed $data
   *   More specific information about the error.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   (optional) A cacheable dependency.
   *
   * @return static
   */
  public static function invalidParams($data = NULL, CacheableDependencyInterface $cacheability = NULL) {
    return new static(static::INVALID_PARAMS, static::$errorMessages[static::INVALID_PARAMS], $data ?: static::$errorMeanings[static::INVALID_PARAMS], $cacheability);
  }

  /**
   * Constructs a new internal error.
   *
   * @param mixed $data
   *   More specific information about the error.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   *   (optional) A cacheable dependency.
   *
   * @return static
   */
  public static function internalError($data = NULL, CacheableDependencyInterface $cacheability = NULL) {
    return new static(static::INTERNAL_ERROR, static::$errorMessages[static::INTERNAL_ERROR], $data ?: static::$errorMeanings[static::INTERNAL_ERROR], $cacheability);
  }

}
