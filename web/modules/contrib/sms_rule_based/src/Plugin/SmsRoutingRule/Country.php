<?php

/**
 * @file
 * Contains \Drupal\sms_rule_based\Plugin\SmsRoutingRule\Country.
 */

namespace Drupal\sms_rule_based\Plugin\SmsRoutingRule;

use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;
use Drupal\sms_country\Utility\CountryCodes;

/**
 * @SmsRoutingRule(
 *   id = "country",
 *   label = @Translation("Country"),
 *   description = @Translation("The destination country of the sms message."),
 * );
 */
class Country extends SmsRoutingRulePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getWidget() {
    return [
      '#type' => 'select',
      '#options' => array('0' => '-- Select Country --') + CountryCodes::getCountryCodes(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function match(array $numbers, array $context) {
    $return_numbers = array();
    foreach ($numbers as $k => $number) {
      if ($this->satisfiesExpression($this->getCountryCode($number))) {
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
