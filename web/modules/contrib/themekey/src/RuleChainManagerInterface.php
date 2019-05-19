<?php

namespace Drupal\themekey;

/**
 * ThemeKey Rule Chain manager.
 */
interface RuleChainManagerInterface {

  public function getChain();

  public function setChain($chain);

  public function getOptimizedChain();

  public function rebuildChain();

  public function rebuildOptimizedChain();
}
