<?php

/**
 * @file
 * Definition of CallbackReference.
 */

namespace WoW\Core\Callback;

use WoW\Core\CallbackInterface;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;

/**
 * Returns the referenced object.
 */
class CallbackReference implements CallbackInterface {

  private $reference;

  /**
   * Constructs a CallbackReference object.
   *
   * @param $reference
   */
  public function __construct($reference) {
    $this->reference = $reference;
  }

  /**
   * (non-PHPdoc)
   * @see CallbackInterface::process()
   */
  public function process(ServiceInterface $service, Response $response) {
    return $this->reference;
  }

}
