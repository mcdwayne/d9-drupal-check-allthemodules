<?php

/**
 * @file
 * Definition of Handler.
 */

namespace WoW\Core\Handler;

use WoW\Core\CallbackInterface;
use WoW\Core\Callback\CallbackException;
use WoW\Core\Callback\CallbackReference;
use WoW\Core\Callback\CallbackUserFunc;
use WoW\Core\HandlerInterface;
use WoW\Core\Request;
use WoW\Core\ServiceInterface;

/**
 * The Handler class executes a request object and process a registered handler
 * for the response code. It supports a default callback (0) in case no handler
 * matches the response code.
 */
class Handler implements HandlerInterface {

  /**
   * The Service.
   *
   * @var ServiceInterface
   */
  protected $service;

  /**
   * The Request.
   *
   * @var Request
   */
  protected $request;

  /**
   * A keyed array containing handlers callback.
   *
   * @var CallbackInterface
   */
  protected $handlers;

  /**
   * Constructs a Handler object.
   *
   * @param ServiceInterface $service
   *   A service object.
   * @param Request $request
   *   A request object.
   */
  public function __construct(ServiceInterface $service, Request $request) {
    $this->service = $service;
    $this->request = $request;
    $this->handlers = array();
  }

  /**
   * (non-PHPdoc)
   * @see HandlerInterface::execute()
   */
  public function execute() {
    $response = $this->request->execute();

    // Checks if a handler has been registered for this response code.
    if (isset($this->handlers[$key = $response->getCode()])
     || isset($this->handlers[$key = 0])) {
      return  $this->handlers[$key]->process($this->service, $response) ?: $response;
    }

    // No handlers has been defined for this response.
    return $response;
  }

  /**
   * (non-PHPdoc)
   * @see HandlerInterface::mapCallback()
   */
  public function mapCallback($code, CallbackInterface $callback) {
    $this->handlers[$code] = $callback;
    return $this;
  }

  /**
   * (non-PHPdoc)
   * @see HandlerInterface::mapFunction()
   */
  public function mapFunction($code, $function, array $parameters = array()) {
    return $this->mapCallback($code, new CallbackUserFunc($function, $parameters));
  }

  /**
   * (non-PHPdoc)
   * @see HandlerInterface::mapMethod()
   */
  public function mapMethod($code, $class, $method, array $parameters = array()) {
    return $this->mapCallback($code, new CallbackUserFunc(array($class, $method), $parameters));
  }

  /**
   * (non-PHPdoc)
   * @see HandlerInterface::mapValue()
   */
  public function mapValue($code, $reference) {
    return $this->mapCallback($code, new CallbackReference($reference));
  }

  /**
   * (non-PHPdoc)
   * @see HandlerInterface::mapException()
   */
  public function mapException($code) {
    return $this->mapCallback($code, new CallbackException());
  }

}
