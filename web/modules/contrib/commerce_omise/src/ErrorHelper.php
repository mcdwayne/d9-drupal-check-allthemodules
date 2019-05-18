<?php

namespace Drupal\commerce_omise;

use Drupal\commerce_payment\Exception\AuthenticationException;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\InvalidResponseException;

use OmiseException;
use OmiseInvalidCardException;
use OmiseBadRequestException;
use OmiseAuthenticationFailureException;

/**
 * Translates Omise exceptions and errors into Commerce exceptions.
 */
class ErrorHelper {

  /**
   * Translates Omise exceptions into Commerce exceptions.
   *
   * @param \OmiseException $exception
   *   The Omise exception.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleException(OmiseException $exception) {
    if ($exception instanceof OmiseInvalidCardException) {
      throw new DeclineException('We encountered an error processing your card details. Please verify your details and try again.');
    }
    elseif ($exception instanceof OmiseBadRequestException) {
      throw new InvalidRequestException('Invalid parameters were supplied to Omise\'s API.');
    }
    elseif ($exception instanceof OmiseAuthenticationFailureException) {
      throw new AuthenticationException('Omise authentication failed.');
    }
    else {
      throw new InvalidResponseException($exception->getMessage());
    }
  }

}
