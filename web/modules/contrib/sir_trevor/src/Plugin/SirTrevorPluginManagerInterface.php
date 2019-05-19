<?php

namespace Drupal\sir_trevor\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

interface SirTrevorPluginManagerInterface extends PluginManagerInterface {
  /**
   * @return \Drupal\sir_trevor\Plugin\SirTrevorPlugin[]
   */
  public function createInstances();

  /**
   * @return \Drupal\sir_trevor\Plugin\SirTrevorPlugin[]
   *  All known SirTrevorPlugins of the type 'block'.
   */
  public function getBlocks();

  /**
   * @return \Drupal\sir_trevor\Plugin\SirTrevorPlugin[]
   */
  public function getEnabledBlocks();
}
