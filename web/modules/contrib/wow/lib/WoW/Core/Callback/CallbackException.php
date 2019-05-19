<?php

/**
 * @file
 * Definition of CallbackException.
 */

namespace WoW\Core\Callback;

use WoW\Core\CallbackInterface;
use WoW\Core\Response;
use WoW\Core\ResponseException;
use WoW\Core\ServiceInterface;

/**
 * Throws a ResponseException.
 */
class CallbackException implements CallbackInterface {

  /**
   * (non-PHPdoc)
   * @see CallbackInterface::process()
   */
  public function process(ServiceInterface $service, Response $response) {
    throw new ResponseException($response);
  }

}
