<?php

namespace Drupal\drd\Update;

/**
 * Defines the required interface for all DRD Update Test plugins.
 */
interface PluginTestInterface extends PluginInterface {

  /**
   * Start the test process.
   *
   * @param PluginStorageInterface $storage
   *   The storage plugin.
   *
   * @return $this
   */
  public function test(PluginStorageInterface $storage);

  /**
   * Determine if the execution has succeeded.
   *
   * @return bool
   *   TRUE if the execution has succeeded.
   */
  public function hasSucceeded();

}
