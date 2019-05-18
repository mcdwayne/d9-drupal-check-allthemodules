<?php

/**
 * @file
 * Contains \Drupal\sms_rule_based\Plugin\SmsRoutingRule\Recipients.
 */

namespace Drupal\sms_rule_based\Plugin\SmsRoutingRule;

use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;

/**
 * @SmsRoutingRule(
 *   id = "number",
 *   label = @Translation("Number"),
 *   description = @Translation("The recipient number of the SMS message."),
 * );
 */
class Number extends SmsRoutingRulePluginBase {

  public function getWidget() {
    return array(
      '#type' => 'textarea',
      '#columns' => 40,
      '#rows' => 2,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function match(array $numbers, array $context) {
    $return_numbers = array();
    foreach ($numbers as $k => $number) {
      if ($this->satisfiesExpression($number)) {
        $return_numbers[] = $number;
      }
    }
    return $return_numbers;
  }

}
