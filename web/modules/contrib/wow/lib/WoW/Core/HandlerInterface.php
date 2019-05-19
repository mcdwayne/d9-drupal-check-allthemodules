<?php

/**
 * @file
 * Definition of HandlerInterface.
 */

namespace WoW\Core;

/**
 * Maps and executes callback on a response code returned by executing a request
 * object.
 */
interface HandlerInterface {

  /**
   * Adds a callback to the request.
   *
   * @param int $code
   *   The response code or 0 for 'default'.
   * @param CallbackInterface $callback
   *   The callback to be called.
   *
   * @return HandlerInterface
   *   The handler reference.
   */
  public function mapCallback($code, CallbackInterface $callback);

  /**
   * Adds a function callback to the request.
   *
   * @param int $code
   *   The response code or 0 for 'default'.
   * @param string $function
   *   The function to be called.
   * @param array $parameters
   *   (Optional) The parameters to be passed to the callback, as an indexed
   *   array.
   *
   * @return HandlerInterface
   *   The handler reference.
   */
  public function mapFunction($code, $function, array $parameters = array());

  /**
   * Adds a method callback to the request.
   *
   * @param int $code
   *   The response code or 0 for 'default'.
   * @param string|class $class
   *   The class as string or instance.
   * @param string $method
   *   The method to be called.
   * @param array $parameters
   *   (Optional) The parameters to be passed to the callback, as an indexed
   *   array.
   *
   * @return HandlerInterface
   *   The handler reference.
   */
  public function mapMethod($code, $class, $method, array $parameters = array());

  /**
   * Adds a return value to the request.
   *
   * @param int $code
   *   The response code or 0 for 'default'.
   * @param mixed $reference
   *   The object to be returned.
   *
   * @return HandlerInterface
   *   The handler reference.
   */
  public function mapValue($code, $reference);

  /**
   * Adds an exception to the request.
   *
   * @param int $code
   *   The response code or 0 for 'default'.
   *
   * @return HandlerInterface
   *   The handler reference.
   */
  public function mapException($code);

  /**
   * Executes the request.
   *
   * @return Response|mixed
   *   The response returned by the callback or the service Response.
   */
  public function execute();

}
