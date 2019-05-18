<?php

namespace Drupal\drd\Update;

/**
 * Defines the required interface for all DRD Update Build plugins.
 */
interface PluginBuildInterface extends PluginInterface {

  /**
   * Start the build process.
   *
   * @param PluginStorageInterface $storage
   *   The storage plugin.
   * @param \Drupal\drd\Entity\ReleaseInterface[] $releases
   *   The list of releases which need to be updated.
   *
   * @return $this
   */
  public function build(PluginStorageInterface $storage, array $releases);

  /**
   * Start the patch process.
   *
   * @param PluginStorageInterface $storage
   *   The storage plugin.
   *
   * @return $this
   */
  public function patch(PluginStorageInterface $storage);

  /**
   * Determine if the code base has changed.
   *
   * @return bool
   *   TRUE if the code base has changed.
   */
  public function hasChanged();

}
