<?php

namespace Drupal\sms_rule_based;

use Drupal\sms\Message\SmsMessageInterface;

class RuleBasedSmsRouter {

  /**
   * Carries out gateway routing of numbers based on established routing rules.
   *
   * This function takes the recipient numbers of the SMS object supplied and
   * returns an array made of the numbers that match the rules specified in the
   * SMS routing rulesets and the corresponding gateways, while returning those
   * not matching with a false keyword the inline array supplied.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The sms message object.
   * @param \Drupal\sms_rule_based\Entity\SmsRoutingRuleset[] $rulesets
   *   The list of SMS routing rulesets.
   *
   * @return array
   *   An array consisting of the following elements:
   *   - routes: an array keyed by the gateway name that best fits the routing
   *     rules with each value being an array of numbers that would be passed
   *     through that gateway. The list of unrouted numbers (i.e. to be passed
   *     through the default gateway) is keyed by '__default__'.
   *   - order: an array keyed by the ruleset names that were applied with the
   *     array of matching numbers as the value.
   */
  public function routeSmsRecipients(SmsMessageInterface $sms, array $rulesets) {
    $numbers = $sms->getRecipients();
    $context = $sms->getOptions();
    $context['message'] = $sms->getMessage();
    $context['sender'] = $sms->getSenderNumber();
    $context['uid'] = $sms->getUid();
    $context['uuid'] = $sms->getUuid();
    $routing = [];

    /** @var \Drupal\sms_rule_based\Entity\SmsRoutingRuleset $ruleset */
    foreach ($rulesets as $ruleset) {
      if ($ruleset->get('enabled') && $matches = $this->matchRoute($ruleset->getRules(), $numbers, $context, $ruleset->get('_ALL_TRUE_'))) {
        if (!isset($routing['routes'][$ruleset->get('gateway')])) {
          $routing['routes'][$ruleset->get('gateway')] = array();
        }
        // New matches should be merged with previous for that gateway.
        $routing['routes'][$ruleset->get('gateway')] = array_merge($matches, $routing['routes'][$ruleset->get('gateway')]);
        $routing['order'][$ruleset->get('name')] = $matches;
      }
    }
    if ($numbers) {
      $routing['routes']['__default__'] = $numbers;
      $routing['order']['__default__'] = $numbers;
    }
    return $routing;
  }

  /**
   * Matches the destination numbers to the correct gateways.
   *
   * This function is the ruleset matching engine. It takes rules and an array
   * of numbers as parameters and returns an array of gateways that would match
   * the numbers based on the rules defined. The numbers that don't match any
   * rule are returned in the __default__ gateway key.
   *
   * @param \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginInterface[]|\Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginCollection $rules
   *   The set of rules to check for match.
   * @param array $numbers
   *   List of numbers to be matched against the rules.
   * @param $context
   *   Additional contextual information.
   * @param bool $all_true
   *   Whether all the rules must pass before the route is matched.
   *
   * @return array
   *   An array keyed by gateway id and array of recipient numbers to be sent
   *   via that gateway based on specified rulesets.
   */
  protected function matchRoute($rules, array &$numbers, $context, $all_true = FALSE) {
    // No need to attempt matching if there are no numbers.
    if (empty($numbers)) {
      return [];
    }

    // Run through all the rules, remove numbers that match the rules from the
    // $numbers array and add to the return array.
    // Different code paths needed for all rules being true (conjunction) and
    // any rule being true.
    if ($all_true) {
      $ret = $numbers;
      foreach ($rules as $rule) {
        $ret = array_intersect($ret, $rule->match($numbers, $context));
      }
    }
    else {
      $ret = array();
      foreach ($rules as $name => $rule) {
        $ret = array_unique(array_merge($ret, $rule->match($numbers, $context)));
      }
    }
    // Remove matching numbers from original array.
    // Use array_values to re-index the array.
    $numbers = array_values(array_diff($numbers, $ret));
    return $ret;
  }

}
