<?php

namespace Drupal\drd\Update;

/**
 * Manages discovery and instantiation of DRD Update Finish plugins.
 */
class ManagerFinish extends Manager {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'finish';
  }

  /**
   * {@inheritdoc}
   */
  public function getSubDir() {
    return 'Plugin/Update/Finish';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginInterface() {
    return 'Drupal\drd\Update\PluginFinishInterface';
  }

}
