<?php

namespace Drupal\commerce_payone;

use Drupal\commerce_payment\Exception\AuthenticationException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Exception\SoftDeclineException;
use Exception;

/**
 * Translates exceptions and Payone errors into Commerce exceptions.
 *
 * @see 5.7 Error messages (Technical reference, PAYONE Client / Server API)
 */
class ErrorHelper {

  /**
   * Translates exceptions into Commerce exceptions.
   *
   * @param \Exception $exception
   *   The exception.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleException(Exception $exception) {
    if ($exception instanceof HardDeclineException) {
      throw new HardDeclineException($exception->getMessage());
    }
    elseif ($exception instanceof InvalidRequestException) {
      throw new InvalidRequestException($exception->getMessage());
    }
    else {
      throw new PaymentGatewayException($exception->getMessage());
    }
  }

  /**
   * Translates Payone errors into Commerce exceptions.
   *
   * @param object $result
   *   The Payone result object.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleErrors($result) {
    if ($result->status != 'ERROR') {
      return;
    }

    \Drupal::logger('commerce_payone')->warning('API request returned following error: @errorcode, @errormsg', [
      '@errorcode' => $result->errorcode,
      '@errormsg' => $result->errormessage,
    ]);

    // Payone API error messages are documented in section
    // 5.7 Error messages (Technical reference, Client or Server API).
    // Validation errors can be due to a module error (mapped to
    // InvalidRequestException) or due to a user input error (mapped to
    // a HardDeclineException).
    $hard_decline_codes = [1, 2, 4, 5, 7, 12, 13, 14, 30, 31, 34, 43, 56, 62, 701, 702, 722, 732];
    if (in_array($result->errorcode, $hard_decline_codes)) {
      throw new HardDeclineException($result->customermessage, $result->errorcode);
    }
    else {
      throw new InvalidRequestException($result->customermessage, $result->errorcode);
    }
  }

}
