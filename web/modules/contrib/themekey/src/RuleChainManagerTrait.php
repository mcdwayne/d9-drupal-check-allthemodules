<?php

namespace Drupal\themekey;

use Drupal\themekey\RuleChainManagerInterface;

trait RuleChainManagerTrait {

  /**
   * @var
   */
  private $ruleChainManager;

  /**
   * Gets the ThemeKey Rule Chain manager.
   *
   * @return \Drupal\themekey\RuleChainManagerInterface
   *   The ThemeKey Rule Chain manager.
   */
  protected function getRuleChainManager() {
    if (!$this->ruleChainManager) {
      $this->ruleChainManager = \Drupal::service('themekey.rule_chain_manager');
    }

    return $this->ruleChainManager;
  }

  /**
   * Sets the ThemeKey Rule Chain manager to use.
   *
   * @param \Drupal\themekey\RuleChainManagerInterface
   *   The ThemeKey Rule Chain manager.
   *
   * @return $this
   */
  public function setRuleChainManager(RuleChainManagerInterface $ruleChainManager) {
    $this->ruleChainManager = $ruleChainManager;

    return $this;
  }

}
