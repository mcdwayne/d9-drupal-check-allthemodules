<?php

namespace Drupal\commerce_usaepay;

use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Exception\SoftDeclineException;
use Exception;
use SoapFault;

/**
 * Translates USAePay exceptions and errors into Commerce exceptions.
 */
class ErrorHelper {

  /**
   * Translates USAePay errors into Commerce exceptions.
   *
   * @param \Exception $exception
   *   The PHP exception.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleException(Exception $exception) {

    if ($exception instanceof SoapFault) {
      \Drupal::logger('commerce_usaepay')->warning($exception->faultstring);
      throw new PaymentGatewayException($exception->faultstring);
    }
    else {
      \Drupal::logger('commerce_usaepay')->warning($exception->getMessage());
      throw new PaymentGatewayException($exception->getMessage());
    }
  }

  /**
   * Translates USAePay errors into Commerce exceptions.
   *
   * @param object $response
   *   The USAePay response object.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleErrors($response) {

    if ($response->ResultCode === 'A') {
      return;
    }
    elseif ($response->ResultCode === 'D') {

      $soft_decline_codes = [10251, 10262, 10265, 10275];
      $hard_decline_codes = [
        10127, 10204, 10205, 10212, 10215, 10225,
        10255, 10257, 10262, 10278, 10297,
      ];

      \Drupal::logger('commerce_usaepay')->warning($response->Error . ' (' . $response->ErrorCode . ')');
      if (in_array($response->ErrorCode, $soft_decline_codes)) {
        throw new SoftDeclineException($response->Error, $response->ErrorCode);
      }
      elseif (in_array($response->ErrorCode, $hard_decline_codes)) {
        throw new HardDeclineException($response->Error, $response->ErrorCode);
      }
      else {
        throw new InvalidRequestException($response->Error, $response->ErrorCode);
      }
    }
    elseif ($response->ResultCode === 'E') {
      \Drupal::logger('commerce_usaepay')->warning($response->Error . ' (' . $response->ErrorCode . ')');
      throw new InvalidRequestException($response->Error, $response->ErrorCode);
    }
    elseif ($response->ResultCode === 'V') {
      \Drupal::logger('commerce_usaepay')->warning($response->Error . ' (' . $response->ErrorCode . ')');
      throw new DeclineException();
    }
  }

}
