<?php

namespace Drupal\sms_mailup\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\sms\Entity\SmsGatewayInterface;

/**
 * Checks if the gateway is a mailup instance.
 */
class GatewayIsMailup implements AccessInterface {

  /**
   * Check if the gateway is a mailup plugin instance.
   */
  public function access(SmsGatewayInterface $sms_gateway) {
    return AccessResult::allowedIf($sms_gateway->getPluginId() === 'mailup');
  }

}
