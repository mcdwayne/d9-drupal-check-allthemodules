<?php

namespace Drupal\drd\Update;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * DRD Update Plugins Manager.
 *
 * Provides an interface for the discovery and instantiation of DRD Update
 * plugins for storage, build and process steps.
 */
interface ManagerInterface extends PluginManagerInterface {

  /**
   * Get the update plugin type.
   *
   * @return string
   *   The update plugin type.
   */
  public function getType();

  /**
   * Get update plugin sub directory.
   *
   * @return string
   *   The update plugin sub directory.
   */
  public function getSubDir();

  /**
   * Get the update plugin interface.
   *
   * @return string
   *   The update plugin interface.
   */
  public function getPluginInterface();

  /**
   * Get a list of all plugins of that type.
   *
   * @return array
   *   List of all plugins of that type.
   */
  public function getSelect();

}
