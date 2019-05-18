<?php

namespace Drupal\drd\Update;

/**
 * Manages discovery and instantiation of DRD Update Test plugins.
 */
class ManagerTest extends Manager {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'test';
  }

  /**
   * {@inheritdoc}
   */
  public function getSubDir() {
    return 'Plugin/Update/Test';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginInterface() {
    return 'Drupal\drd\Update\PluginTestInterface';
  }

}
