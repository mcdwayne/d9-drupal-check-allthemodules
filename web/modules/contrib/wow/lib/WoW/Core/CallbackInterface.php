<?php

/**
 * @file
 * Definition of CallbackInterface.
 */

namespace WoW\Core;

/**
 * Processes callback that react on a response object.
 */
interface CallbackInterface {

  /**
   * Executes a callback.
   *
   * @param ServiceInterface $service
   *   The service responsible of the call.
   * @param Response $response
   *   The response object.
   */
  public function process(ServiceInterface $service, Response $response);

}
