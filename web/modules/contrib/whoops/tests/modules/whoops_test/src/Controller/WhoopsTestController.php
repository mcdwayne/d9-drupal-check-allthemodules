<?php

namespace Drupal\whoops_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Returns responses for whoops test module routes.
 */
class WhoopsTestController extends ControllerBase {

  /**
   * Tests exception handling.
   *
   * @throws \RuntimeException
   */
  public function exception() {
    throw new \RuntimeException('Tests exception handling');
  }

  /**
   * Tests http exception handling.
   *
   * NOTE: Since all 4xx Http Status code errors are caught by the core's
   * subscribers use a non existent Http Status code for test the behaviour.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function httpException() {
    throw new HttpException(599, 'Non Existent Http Status');
  }

  /**
   * Tests fatal error handling.
   */
  public function fatalError() {
    undefined();

    return [];
  }

  /**
   * Tests error handling.
   */
  public function error() {
    $undefined[0];
    return [];
  }

}
