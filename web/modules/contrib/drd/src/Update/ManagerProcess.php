<?php

namespace Drupal\drd\Update;

/**
 * Manages discovery and instantiation of DRD Update Process plugins.
 */
class ManagerProcess extends Manager {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'process';
  }

  /**
   * {@inheritdoc}
   */
  public function getSubDir() {
    return 'Plugin/Update/Process';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginInterface() {
    return 'Drupal\drd\Update\PluginProcessInterface';
  }

}
