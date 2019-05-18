<?php

namespace Drupal\icons\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Icon Set entities.
 */
interface IconSetInterface extends ConfigEntityInterface {

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\icons\IconLibraryPluginInterface
   *   The plugin instance for this icon set.
   */
  public function getPlugin();

  /**
   * Encapsulates the creation of the icon library LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The icon library plugin collection.
   */
  public function getPluginCollection();

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID for this icon provider.
   */
  public function getPluginId();

}
