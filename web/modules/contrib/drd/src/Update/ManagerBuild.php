<?php

namespace Drupal\drd\Update;

/**
 * Manages discovery and instantiation of DRD Update Build plugins.
 */
class ManagerBuild extends Manager {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'build';
  }

  /**
   * {@inheritdoc}
   */
  public function getSubDir() {
    return 'Plugin/Update/Build';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginInterface() {
    return 'Drupal\drd\Update\PluginBuildInterface';
  }

}
