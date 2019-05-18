<?php

/**
 * @file
 * Contains Request logger middleware for console logger module.
 */

namespace Drupal\console_logger\StackMiddleware;

use Drupal\Component\Utility\Timer;
use Drupal\console_logger\RequestLogger;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RequestLoggerMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The main Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $drupalKernel;

  /**
   * The request logger service.
   *
   * @var \Drupal\console_logger\RequestLogger
   */
  protected $requestLogger;

  /**
   * Constructs a new KernelPreHandle instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The wrapped HTTP kernel.
   *
   * @param \Drupal\Core\DrupalKernelInterface $drupal_kernel
   *   The main Drupal kernel.
   *
   * @param \Drupal\console_logger\RequestLogger $requestLogger
   *   The request logger service.
   */
  public function __construct(HttpKernelInterface $http_kernel, DrupalKernelInterface $drupal_kernel, RequestLogger $requestLogger) {
    $this->httpKernel = $http_kernel;
    $this->drupalKernel = $drupal_kernel;
    $this->requestLogger = $requestLogger;
  }

  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    Timer::start('console_logger');
    Database::startLog('console_logger');
    $this->requestLogger->handleRequest($request, $type);

    return $this->httpKernel->handle($request, $type, $catch);
  }
}
