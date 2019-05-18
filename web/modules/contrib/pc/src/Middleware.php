<?php

namespace Drupal\pc;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * PhpConsole middleware.
 */
class Middleware implements HttpKernelInterface {

  /**
   * The kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a Middleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    Timer::start('pc_page');
    Database::startLog('pc');
    return $this->httpKernel->handle($request, $type, $catch);
  }

}
