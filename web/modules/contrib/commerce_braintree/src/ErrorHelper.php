<?php

namespace Drupal\commerce_braintree;

use Drupal\commerce_payment\Exception\AuthenticationException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_payment\Exception\SoftDeclineException;

/**
 * Translates Braintree exceptions and errors into Commerce exceptions.
 *
 * @see https://developers.braintreepayments.com/reference/general/exceptions/php
 * @see https://developers.braintreepayments.com/reference/response/transaction/php#unsuccessful-result
 */
class ErrorHelper {

  /**
   * Translates Braintree exceptions into Commerce exceptions.
   *
   * @param \Braintree\Exception $exception
   *   The Braintree exception.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleException(\Braintree\Exception $exception) {
    if ($exception instanceof \Braintree\Exception\Authentication) {
      throw new AuthenticationException('Braintree authentication failed.');
    }
    elseif ($exception instanceof \Braintree\Exception\Authorization) {
      throw new AuthenticationException('The used API key is not authorized to perform the attempted action.');
    }
    elseif ($exception instanceof \Braintree\Exception\NotFound) {
      throw new InvalidRequestException('Braintree resource not found.');
    }
    elseif ($exception instanceof \Braintree\Exception\UpgradeRequired) {
      throw new InvalidRequestException('The Braintree client library needs to be updated.');
    }
    elseif ($exception instanceof \Braintree\Exception\TooManyRequests) {
      throw new InvalidRequestException('Too many requests.');
    }
    elseif ($exception instanceof \Braintree\Exception\ServerError) {
      throw new InvalidResponseException('Server error.');
    }
    elseif ($exception instanceof \Braintree\Exception\DownForMaintenance) {
      throw new InvalidResponseException('Request timed out.');
    }
    else {
      throw new InvalidResponseException($exception->getMessage());
    }
  }

  /**
   * Translates Braintree errors into Commerce exceptions.
   *
   * @param object $result
   *   The Braintree result object.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleErrors($result) {
    if ($result->success) {
      return;
    }

    $errors = $result->errors->deepAll();
    if (!empty($errors)) {
      // https://developers.braintreepayments.com/reference/general/validation-errors/all/php
      // Validation errors can be due to a module error (mapped to
      // InvalidRequestException) or due to a user input error (mapped to
      // a HardDeclineException).
      $hard_decline_codes = [81813, 91828, 81736, 81737, 81750, 91568];
      foreach($errors AS $error) {
        if (in_array($error->code, $hard_decline_codes)) {
          throw new HardDeclineException($error->message, $error->code);
        }
        else {
          throw new InvalidRequestException($error->message, $error->code);
        }
      }
    }

    // Both verification and the transaction can result in the same errors.
    $error_statuses = [
      'settlement_declined',
      'gateway_rejected',
      'processor_declined',
    ];
    if ($result->verification && in_array($result->verification->status, $error_statuses)) {
      $error = $result->verification;
      $status = $result->verification->status;
    }
    elseif ($result->transaction && in_array($result->transaction->status, $error_statuses)) {
      $error = $result->transaction;
      $status = $result->transaction->status;
    }

    if ($status == 'settlement_declined') {
      $code = $error->processorSettlementResponseCode;
      $text = $error->processorSettlementResponseText;
      throw new HardDeclineException($text, $code);
    }
    elseif ($status == 'gateway_rejected') {
      $reason = $error->gatewayRejectionReason;
      throw new HardDeclineException('Rejected by the gateway. Reason: ' . $reason);
    }
    elseif ($status == 'processor_declined') {
      // https://developers.braintreepayments.com/reference/general/processor-responses/authorization-responses
      $soft_decline_codes = [
        2000, 2001, 2002, 2003, 2009, 2016, 2021, 2025, 2026, 2033, 2034, 2035,
        2038, 2040, 2042, 2046, 2048, 2050, 2054, 2057, 2062,
      ];
      $code = $error->processorResponseCode;
      $text = $error->processorResponseText;
      if (!empty($error->additionalProcessorResponse)) {
        $text .= ' (' . $error->additionalProcessorResponse . ')';
      }
      if (in_array($code, $soft_decline_codes) || ($code >= 2092 && $code <= 3000)) {
        throw new SoftDeclineException($text, $code);
      }
      else {
        throw new HardDeclineException($text, $code);
      }
    }

    // Throw a fallback exception for everything else.
    throw new InvalidRequestException($result);
  }

}
