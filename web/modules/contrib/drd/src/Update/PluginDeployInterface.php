<?php

namespace Drupal\drd\Update;

/**
 * Defines the required interface for all DRD Update Deploy plugins.
 */
interface PluginDeployInterface extends PluginInterface {

  /**
   * Start the deploy process.
   *
   * @param PluginStorageInterface $storage
   *   The storage plugin.
   *
   * @return $this
   */
  public function deploy(PluginStorageInterface $storage);

  /**
   * Start the deploy process as a dry run.
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
