<?php

namespace Drupal\sms_gateway_base\Plugin\SmsGateway;

/**
 * Holds information on common commands for SMS gateways.
 */
class GatewayCommand {

  /**
   * Send SMS gateway command.
   *
   * @var string
   */
  const SEND = 'send';

  /**
   * Credit's balance gateway command.
   *
   * @var string
   */
  const BALANCE = 'balance';

  /**
   * Delivery reports gateway command.
   *
   * @var string
   */
  const REPORT = 'report';

  /**
   * Gateway general test command.
   *
   * @var string
   */
  const TEST = 'test';

}
