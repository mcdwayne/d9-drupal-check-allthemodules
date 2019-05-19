<?php

/**
 * @file
 * Mocks a Callback object.
 */

namespace Drupal\wow\Mocks;

use WoW\Core\CallbackInterface;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;

/**
 * Callback Mock.
 */
class CallbackMock implements CallbackInterface {

  private $processed = FALSE;
  public $return;

  public function process(ServiceInterface $service, Response $response) {
    $this->processed = TRUE;
    if (isset($this->return)) {
      return $this->return;
    }
  }

  public function processedCalled() {
    return $this->processed;
  }
}
