<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data\Payment;

/**
 * An interface to describe authorization request.
 */
interface AuthorizationRequestInterface extends RequestInterface {

  /**
   * Set automatic capture.
   *
   * @param bool $status
   *   The status indicating whether auto capture is allowed or not.
   *
   * @return $this
   *   The self.
   */
  public function setAllowAutoCapture(bool $status) : AuthorizationRequestInterface;

}
