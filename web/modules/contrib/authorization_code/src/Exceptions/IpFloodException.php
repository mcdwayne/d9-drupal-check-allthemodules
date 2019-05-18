<?php

namespace Drupal\authorization_code\Exceptions;

/**
 * IP flood exception.
 */
class IpFloodException extends \Exception {

  /**
   * The offending IP.
   *
   * @var string
   */
  private $ip;

  /**
   * IpFloodException constructor.
   *
   * @param string $ip
   *   The offending IP.
   * @param \Throwable $previous
   *   The previous exception.
   */
  public function __construct(string $ip, \Throwable $previous = NULL) {
    parent::__construct(sprintf('IP flood exception for: %s', $ip), 0, $previous);
    $this->ip = $ip;
  }

  /**
   * The offending IP.
   *
   * @return string
   *   The offending IP.
   */
  public function getOffendingIp(): string {
    return $this->ip;
  }

}
