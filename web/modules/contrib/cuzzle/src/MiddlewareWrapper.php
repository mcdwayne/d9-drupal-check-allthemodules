<?php

namespace Drupal\cuzzle;

use Drupal\Core\Site\Settings;
use Namshi\Cuzzle\Formatter\CurlFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Middleware to log all HTTP requests as a cURL command.
 */
class MiddlewareWrapper {

  private $logger;

  /**
   * MiddlewareWrapper constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The system logger.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Handler callback for Guzzle.
   *
   * @return \Closure
   *   The handler middleware function.
   */
  public function __invoke() {
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        // Since cURL requests would include credentials, we also require an
        // opt-in via settings.php.
        if (Settings::get('cuzzle_enabled')) {
          $curlCommand = (new CurlFormatter(-1))->format($request, $options);
          $this->logger->debug('HTTP request: %curl', ['%curl' => $curlCommand]);
        }

        return $handler($request, $options);
      };
    };
  }

}
