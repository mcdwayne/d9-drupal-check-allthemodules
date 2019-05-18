<?php

namespace Drupal\cognito\Aws;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * Wraps a cognito result.
 */
class CognitoResult {

  /**
   * The callback result.
   *
   * @var mixed
   */
  protected $result;

  /**
   * The exception if any.
   *
   * @var null|\Exception
   */
  protected $exception;

  /**
   * The is challenge flag.
   *
   * @var bool
   */
  protected $isChallenge;

  /**
   * CognitoResult constructor.
   *
   * @param mixed $result
   *   The result.
   * @param \Exception $exception
   *   The error.
   * @param bool $isChallenge
   *   Is this result a challenge.
   */
  public function __construct($result, \Exception $exception = NULL, $isChallenge = FALSE) {
    $this->result = $result;
    $this->exception = $exception;
    $this->isChallenge = $isChallenge;
  }

  /**
   * Gets the callback result.
   *
   * @return mixed
   *   The callback result.
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Check if the result is an error.
   *
   * @return bool
   *   TRUE if error otherwise FALSE.
   */
  public function hasError() {
    return (bool) $this->exception;
  }

  /**
   * Gets the error.
   *
   * @return null|string
   *   The error.
   */
  public function getError() {
    if ($this->exception instanceof CognitoIdentityProviderException) {
      if ($message = $this->exception->getAwsErrorMessage()) {
        return $message;
      }
    }
    return $this->exception->getMessage();
  }

  /**
   * The error code.
   *
   * @return int|mixed|null|string
   *   The exception code.
   */
  public function getErrorCode() {
    return $this->exception instanceof CognitoIdentityProviderException ?
      $this->exception->getAwsErrorCode() :
      $this->exception->getCode();
  }

  /**
   * Checks if this result is a challenge.
   *
   * @return bool
   *   TRUE if it's a challenge otherwise FALSE;
   */
  public function isChallenge() {
    return $this->isChallenge;
  }

}
