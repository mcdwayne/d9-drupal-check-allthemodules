<?php

namespace Drupal\icon\Plugin\Icon;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface IconSetInterface
 */
interface IconSetInterface extends ContainerAwareInterface, ContainerFactoryPluginInterface, DerivativeInspectionInterface, PluginInspectionInterface {

  /**
   * Retrieves the human readable label for the plugin.
   *
   * @return string
   *   The human readable label.
   */
  public function getLabel();

  /**
   * Retrieves an array of icons grouped by unique name.
   *
   * @return array
   *   Array of icons.
   */
  public function getIcons();

  /**
   * Retrieves the provider name corresponding to the icon set.
   *
   * @return string
   *   The provider.
   */
  public function getProvider();

  /**
   * Retrieves the url for more information regarding the icon set.
   *
   * @return string
   *   The url.
   */
  public function getUrl();

  /**
   * Retrieves the supplemental information for identifying the icon set.
   *
   * @return string
   *   The version.
   */
  public function getVersion();

  /**
   * Retrieves the path where the icon set resource files are located.
   *
   * @return string
   *   The path to the file.
   */
  public function getPath();

  /**
   * Retrieves the renderer the icon set should implement.
   *
   * @return string
   *   The renderer.
   */
  public function getRenderer();

  /**
   * Retrieves an array of settings passed to the renderer.
   *
   * @return array
   *   Array of settings.
   */
  public function getSettings();

  /**
   * Retrieves an array of resources to be loaded alongside the icon set.
   *
   * @return array
   *   The plugin settings.
   */
  public function getAttached();

  /**
   * Processes a IconSet.
   *
   * @return array
   *   An associative array representing an IconSet
   */
  public function process();

  /**
   * Get icon.
   *
   * @return mixed
   *   Icon or FALSE;
   */
  public function getIcon();

  /**
   * Set icon.
   *
   * @param string $value
   *   Custom Icon value.
   * @param string $key
   *   Custom Icon key.
   */
  public function setIcon($value, $key);

}
