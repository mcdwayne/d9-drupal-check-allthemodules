<?php

/**
 * @file
 * Contains \Drupal\sms_rule_based\Plugin\SmsRoutingRule\Recipients.
 */

namespace Drupal\sms_rule_based\Plugin\SmsRoutingRule;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;

/**
 * @SmsRoutingRule(
 *   id = "sendtime",
 *   label = @Translation("Send time"),
 *   description = @Translation("Time that the SMS message is being sent."),
 * );
 */
class Sendtime extends SmsRoutingRulePluginBase {

  public function getWidget() {
    return array(
      '#type' => 'date',
      '#date_format' => 'Y-m-d',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function match(array $numbers, array $context) {
    return $this->satisfiesExpression(REQUEST_TIME) ? $numbers : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getReadableOperand() {
    $datetime = DateTimePlus::createFromFormat('Y-m-d', $this->getOperand());
    return $datetime->format('d-M-y');
  }

}
