<?php

/**
 * @file
 * Contains \Drupal\sms_rule_based\Plugin\SmsRoutingRule\Recipients.
 */

namespace Drupal\sms_rule_based\Plugin\SmsRoutingRule;

use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;

/**
 * @SmsRoutingRule(
 *   id = "recipient_count",
 *   label = @Translation("Recipient count"),
 *   description = @Translation("The number of recipients of the SMS message."),
 * );
 */
class Recipients extends SmsRoutingRulePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getWidget() {
    return array(
      '#type' => 'number',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function match(array $numbers, array $context) {
    // @todo: Should I be checking $numbers or $context['numbers'], since
    // $numbers may change in the course of SMS routing?
    return $this->satisfiesExpression(count($numbers)) ? $numbers : array();
  }

}
