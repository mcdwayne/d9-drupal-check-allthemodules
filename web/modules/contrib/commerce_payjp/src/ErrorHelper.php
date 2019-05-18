<?php

namespace Drupal\commerce_payjp;

use Drupal\commerce_payment\Exception\AuthenticationException;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\InvalidResponseException;

/**
 * Translates Payjp exceptions and errors into Commerce exceptions.
 */
class ErrorHelper {

  /**
   * Translates Payjp exceptions into Commerce exceptions.
   *
   * @param \Payjp\Error\Base $exception
   *   The Payjp exception.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   The Commerce exception.
   */
  public static function handleException(\Payjp\Error\Base $exception) {
    if ($exception instanceof \Payjp\Error\Card) {
      throw new DeclineException('We encountered an error processing your card details. Please verify your details and try again.');
    }
    elseif ($exception instanceof \Payjp\Error\RateLimit) {
      throw new InvalidRequestException('Too many requests.');
    }
    elseif ($exception instanceof \Payjp\Error\InvalidRequest) {
      throw new InvalidRequestException('Invalid parameters were supplied to Pay.JP\'s API.');
    }
    elseif ($exception instanceof \Payjp\Error\Authentication) {
      throw new AuthenticationException('Pay.JP authentication failed.');
    }
    elseif ($exception instanceof \Payjp\Error\ApiConnection) {
      throw new InvalidResponseException('Network communication with Pay.JP failed.');
    }
    else {
      throw new InvalidResponseException($exception->getMessage());
    }
  }

}
