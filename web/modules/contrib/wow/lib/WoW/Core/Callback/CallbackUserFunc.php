<?php

/**
 * @file
 * Definition of CallbackUserFunc.
 */

namespace WoW\Core\Callback;

use WoW\Core\CallbackInterface;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;

/**
 * Returns the result of a function call. The response object is prepended to
 * the parameters array when calling the callback function.
 */
class CallbackUserFunc implements CallbackInterface {

  private $callback;
  private $parameters;

  /**
   * Constructs a CallbackUserFunc object.
   *
   * @param callback $callback
   * @param array $parameters
   */
  public function __construct($callback, $parameters) {
    $this->callback = $callback;
    $this->parameters = $parameters;
  }

  /**
   * (non-PHPdoc)
   * @see CallbackInterface::process()
   */
  public function process(ServiceInterface $service, Response $response) {
    // Adds the service and response object as former parameters.
    array_unshift($this->parameters, $service, $response);
    $return = call_user_func_array($this->callback, $this->parameters);
    return isset($return) ? $return : $response;
  }

}
