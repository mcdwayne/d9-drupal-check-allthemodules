<?php

namespace Drupal\drd\Update;

/**
 * Manages discovery and instantiation of DRD Update Deploy plugins.
 */
class ManagerDeploy extends Manager {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'deploy';
  }

  /**
   * {@inheritdoc}
   */
  public function getSubDir() {
    return 'Plugin/Update/Deploy';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginInterface() {
    return 'Drupal\drd\Update\PluginDeployInterface';
  }

}
