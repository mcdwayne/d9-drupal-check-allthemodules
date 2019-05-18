<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/5/17
 * Time: 8:08 AM
 */

namespace Drupal\basicshib\Exception;


class BasicShibException extends \Exception {
  /**
   * @var array
   */
  private $context = [];

  /**
   * Create an exception with placeholder context.
   *
   * @param $message
   * @param array $context
   * @param int $code
   * @param null $previous
   *
   * @return static
   */
  public static function createWithContext($message, array $context = [], $code = 0, $previous = null) {
    $exception = new static($message, $code, $previous);
    $exception->setContext($context);
    return $exception;
  }

  /**
   * @return array
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * @param array $context
   */
  public function setContext($context) {
    $this->context = $context;
  }

}
