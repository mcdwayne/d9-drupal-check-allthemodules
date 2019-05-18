<?php

namespace Drupal\commerce_iats\Exception;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class GenericPaymentGatewayException.
 */
class GenericPaymentGatewayException extends PaymentGatewayException {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct($message = "", $code = 0, \Throwable $previous = NULL) {
    if (!$message) {
      $message = $this->t('We encountered an error processing your payment method. Please verify your details and try again.');
    }
    parent::__construct($message, $code, $previous);
  }

}
