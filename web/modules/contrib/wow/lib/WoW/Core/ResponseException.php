<?php

/**
 * @file
 * Definition of ResponseException.
 */
namespace WoW\Core;

/**
 * Defines the ResponseException class.
 */
class ResponseException extends \Exception {

  /**
   * Constructs an Exception object.
   *
   * @param Response $response
   *   The response returned by the service.
   */
  public function __construct(Response $response) {
    parent::__construct($response->getData('reason'), $response->getCode());
  }

}

