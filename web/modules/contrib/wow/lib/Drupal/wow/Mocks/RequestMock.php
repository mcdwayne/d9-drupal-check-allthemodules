<?php

/**
 * @file
 * Mocks a Request object.
 */

namespace Drupal\wow\Mocks;

use WoW\Core\Request;

/**
 * Request Mock.
 */
class RequestMock extends Request {

  public $path;
  public $query;
  public $headers;

  private $response;
  private $executed = FALSE;

  public function __construct($response) {
    $this->response = $response;
  }

  public function execute() {
    $this->executed = TRUE;
    return $this->response;
  }

  public function executedCalled() {
    return $this->executed;
  }

}
