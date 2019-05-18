<?php

namespace Drupal\drd\Update;

/**
 * Defines the required interface for all DRD Update Finish plugins.
 */
interface PluginFinishInterface extends PluginInterface {

  /**
   * Start the finish process.
   *
   * @param PluginStorageInterface $storage
   *   The storage plugin.
   *
   * @return $this
   */
  public function finish(PluginStorageInterface $storage);

  /**
   * Start the finish process as a dry run.
   *
   * @param PluginStorageInterface $storage
   *   The storage plugin.
   *
   * @return $this
   */
  public function dryRun(PluginStorageInterface $storage);

  /**
   * Determine if the execution has succeeded.
   *
   * @return bool
   *   TRUE if the execution has succeeded.
   */
  public function hasSucceeded();

}
