<?php

/**
 * @file
 * Contains \Drupal\sms_rule_based\Plugin\SmsRoutingRule\Area.
 */

namespace Drupal\sms_rule_based\Plugin\SmsRoutingRule;

use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;
use Drupal\sms_country\Utility\CountryCodes;

/**
 * @SmsRoutingRule(
 *   id = "area",
 *   label = @Translation("Area code"),
 *   description = @Translation("Area code (the 3 digits immediately following the country code)."),
 * );
 */
class Area extends SmsRoutingRulePluginBase {

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
    $return_numbers = array();
    foreach ($numbers as $k => $number) {
      $start = strlen($this->getCountryCode($number));
      // This would have worked except for the IN operation where the operand
      // $exp is many in one.
      // if ($this->satisfiesExpression(substr($num, $start, strlen($exp)))) $retnums[] = $num;
      // @todo: Find a better way to implement variable length prefixes like
      // 7025, 702, 704, etc.
      if ($this->satisfiesExpression(substr($number, $start, 3))) {
        $return_numbers[] = $number;
      }
    }
    return $return_numbers;
  }


  /**
   * Returns the country code given a particular number.
   *
   * @param string $number
   *   The number for which a country code is found.
   *
   * @return string
   *   The country code.
   */
  protected function getCountryCode($number) {
    return CountryCodes::getCountryCode($number);
  }

}
