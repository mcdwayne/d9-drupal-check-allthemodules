<?php
/**
 * @file
 * Contains OperatorManager.
 */

namespace Drupal\themekey;

use Drupal\themekey\Entity\ThemeKeyRule;

/**
 * ThemeKey Rule Chain manager.
 */
class RuleChainManager implements RuleChainManagerInterface {

  protected $ruleChainConfig;

  protected function ruleChainConfig() {
    if (!$this->ruleChainConfig) {
      $this->ruleChainConfig = \Drupal::config('themekey.rule_chain');
    }
    return $this->ruleChainConfig;
  }

  public function setRuleChainConfig($ruleChainConfig) {
    $this->ruleChainConfig = $ruleChainConfig;
  }

  public function getChain() {
    return $this->ruleChainConfig()->get('chain');
  }

  public function setChain($chain) {
    $this->ruleChainConfig()->set('chain',
      $this->sortChain($chain)
    );
    $this->ruleChainConfig()->save();
  }

  /**
   * Triggerd by EventSubscriber
   */
  public function rebuildOptimizedChain() {
    $chain = $this->getChain();

    // Prepare optimized chain.
    $disabledRules = array();
    foreach ($chain as $ruleId => $rule) {
      if (!$rule['enabled'] || in_array($rule['parent'], $disabledRules)) {
        $disabledRules[] = $ruleId;
        unset($chain[$ruleId]);
      }
    }

    \Drupal::state()->set('themekey.optimized_rule_chain', $chain);
  }

  public function getOptimizedChain() {
    return \Drupal::state()->get('themekey.optimized_rule_chain');
  }

  public function rebuildChain() {
    $changed = FALSE;

    $chain = $this->ruleChainConfig()->get('chain');
    if (!is_array($chain)) {
      $chain = array();
    }

    // Get max weight.
    $weight = 0;
    foreach ($chain as $element) {
      if ($element['weight'] > $weight) {
        $weight = $element['weight'];
      }
    }

    $rules = ThemeKeyRule::loadMultiple();

    // Add new rules to chain.
    $ruleIds = array();
    foreach ($rules as $rule) {
      $id = $rule->id();
      $ruleIds[] = $id;
      if (!array_key_exists($id, $chain)) {
        $chain[$id] = array(
          'weight' => ++$weight,
          'parent' => NULL,
          'enabled' => TRUE,
          'depth' => 0,
        );
        $changed = TRUE;
      }
    }

    // Remove deleted rules from chain.
    $deletedRuleIds = array_diff(array_keys($chain), $ruleIds);
    foreach ($deletedRuleIds as $id) {
      $parent = $chain[$id]['parent'];
      unset($chain[$id]);
      foreach ($chain as $element) {
        if ($element['parent'] == $id) {
          $element['parent'] = $parent;
        }
      }
      $changed = TRUE;
    }

    if ($changed) {
      $this->setChain($chain);
    }
  }

  protected function sortChain($chain, $parent = NULL, $depth = 0) {
    $sorted_rules = array();
    $sorted_chain = array();

    foreach ($chain as $ruleId => $ruleMetaData) {
      if ($ruleMetaData['parent'] == $parent) {
        $sorted_rules[$ruleId] = $ruleMetaData['weight'];
      }
    }

    if (!empty($sorted_rules)) {
      asort($sorted_rules, SORT_NUMERIC);
      foreach (array_keys($sorted_rules) as $ruleId) {
        $sorted_chain[$ruleId] = $chain[$ruleId];
        $sorted_chain[$ruleId]['depth'] = $depth;
        $sorted_chain = array_merge(
          $sorted_chain,
          $this->sortChain($chain, $ruleId, $depth + 1)
        );
      }
    }

    return $sorted_chain;
  }
}