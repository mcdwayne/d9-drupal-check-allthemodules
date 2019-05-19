<?php

namespace Drupal\whoops\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

/**
 * Default handling for errors.
 *
 * This middleware registers whoops globally, allowing to handle any php fatal
 * error or exception not caught elsewhere and report it as whoops error page.
 */
class WhoopsMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $kernel;

  /**
   * Constructs a new WhoopsMiddleware.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $app
   *   The wrapped HTTP kernel.
   */
  public function __construct(HttpKernelInterface $app) {
    $this->kernel = $app;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($type === self::MASTER_REQUEST) {
      $this->registerWhoops();
    }

    return $this->kernel->handle($request, $type, $catch);
  }

  /**
   * Registers whoops as error handler.
   *
   * Every php fatal error or uncaught exception is handled by the whoops
   * instance registered in this class.
   */
  protected function registerWhoops() {
    $whoops = new Whoops();
    $whoops->pushHandler(new PrettyPageHandler());
    // Do not convert php non-fatal errors in exceptions in all the code base.
    $whoops->silenceErrorsInPaths('/(.*)/', E_STRICT | E_DEPRECATED | E_NOTICE | E_WARNING);
    $whoops->register();

    // All php non-fatal errors are silenced by whoops but is desirable to
    // show error messages to the developer respecting the Drupal's error_level
    // configuration; To achieve this purpose the default error handler is
    // restored. All php fatal errors are handled and caught by whoops in a
    // shutdown function.
    // @see \Whoops\Run\handleShutdown()
    restore_error_handler();
  }

}
