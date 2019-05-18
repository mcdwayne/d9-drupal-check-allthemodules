<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\Payment\AuthorizationRequestInterface;

/**
 * Value object for sending authorization requests.
 */
class AuthorizationRequest extends Request implements AuthorizationRequestInterface {

  /**
   * {@inheritdoc}
   */
  public function setAllowAutoCapture(bool $status) : AuthorizationRequestInterface {
    $this->data['auto_capture'] = $status;

    return $this;
  }

}
