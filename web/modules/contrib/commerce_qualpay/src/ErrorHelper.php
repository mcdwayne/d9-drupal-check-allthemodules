<?php

namespace Drupal\commerce_qualpay;

use Drupal\commerce_payment\Exception\AuthenticationException;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\InvalidResponseException;

/**
 * Translates qualpay exceptions and errors into Commerce exceptions.
 */
class ErrorHelper {

  /**
   * Translates qualpay exceptions into Commerce exceptions.
   *
   * @param \qualpay\Error\Base $exception
   *   The qualpay exception.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleException(\qualpay\Error\Base $exception) {
    if ($exception instanceof \Qualpay\Error\Card) {
      \Drupal::logger('commerce_qualpay')->warning($exception->getMessage());
      if ($exception->getStripeCode() == 'card_declined' && $exception->getDeclineCode() == 'card_not_supported') {
        // qualpay only supports Visa/MasterCard/Amex for non-USD transactions.
        // @todo Find a better way to communicate this to the customer.
        $message = t('Your card is not supported. Please use a Visa, MasterCard, or American Express card.');
        drupal_set_message($message, 'warning');
        throw new HardDeclineException($message);
      }
      else {
        throw new DeclineException('We encountered an error processing your card details. Please verify your details and try again.');
      }
    }
    elseif ($exception instanceof \Qualpay\Error\RateLimit) {
      \Drupal::logger('commerce_qualpay')->warning($exception->getMessage());
      throw new InvalidRequestException('Too many requests.');
    }
    elseif ($exception instanceof \Qualpay\Error\InvalidRequest) {
      \Drupal::logger('commerce_qualpay')->warning($exception->getMessage());
      throw new InvalidRequestException('Invalid parameters were supplied to qualpay\'s API.');
    }
    elseif ($exception instanceof \Qualpay\Error\Authentication) {
      \Drupal::logger('commerce_qualpay')->warning($exception->getMessage());
      throw new AuthenticationException('qualpay authentication failed.');
    }
    elseif ($exception instanceof \Qualpay\Error\ApiConnection) {
      \Drupal::logger('commerce_qualpay')->warning($exception->getMessage());
      throw new InvalidResponseException('Network communication with qualpay failed.');
    }
    elseif ($exception instanceof \Qualpay\Error\Base) {
      \Drupal::logger('commerce_qualpay')->warning($exception->getMessage());
      throw new InvalidResponseException('There was an error with qualpay request.');
    }
    else {
      throw new InvalidResponseException($exception->getMessage());
    }
  }

  /**
   * Translates qualpay errors into Commerce exceptions.
   *
   * @todo
   *   Make sure if this is really needed or handleException cover all
   *   possible errors.
   *
   * @param object $result
   *   The qualpay result object.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleErrors($result) {
    $result_data = $result->__toArray();
    if ($result_data['status'] == 'succeeded') {
      return;
    }

    // @todo: Better handling for possible qualpay errors.
    if (!empty($result_data['failure_code'])) {
      $failure_code = $result_data['failure_code'];
      $hard_decline_codes = ['processing_error', 'missing', 'card_declined'];
      if (in_array($failure_code, $hard_decline_codes)) {
        throw new HardDeclineException($result_data['failure_message'], $failure_code);
      }
      else {
        throw new InvalidRequestException($result_data['failure_message'], $failure_code);
      }
    }
  }

}
