<?php

/**
 * @file
 * Contains \Drupal\sms_rule_based\Plugin\SmsRoutingRule\Sender.
 */

namespace Drupal\sms_rule_based\Plugin\SmsRoutingRule;

use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;

/**
 * @SmsRoutingRule(
 *   id = "sender",
 *   label = @Translation("Sender"),
 *   description = @Translation("The sender of the SMS message."),
 * );
 */
class Sender extends SmsRoutingRulePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getWidget() {
    return [
      '#type' => 'textarea',
      '#columns' => 40,
      '#rows' => 2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function match(array $numbers, array $context) {
    return $this->satisfiesExpression($context['sender']) ? $numbers : array();
  }

}
