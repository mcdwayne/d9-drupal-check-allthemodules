<?php

namespace Drupal\drd\Update;

/**
 * Defines the required interface for all DRD Update Process plugins.
 */
interface PluginProcessInterface extends PluginInterface {

  /**
   * Start the processing process.
   *
   * @param PluginStorageInterface $storage
   *   The storage plugin.
   *
   * @return $this
   */
  public function process(PluginStorageInterface $storage);

  /**
   * Determine if the execution has succeeded.
   *
   * @return bool
   *   TRUE if the execution has succeeded.
   */
  public function hasSucceeded();

}
