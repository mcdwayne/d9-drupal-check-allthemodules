<?php

/**
 * @file
 * Contains \Drupal\quick_pages\MainContentInterface.
 */

namespace Drupal\quick_pages;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface definition for 'main_content_source' plugins.
 */
interface MainContentInterface extends PluginFormInterface, PluginInspectionInterface {

  /**
   * Returns main content.
   *
   * @return array|null
   *   Render array or null.
   */
  public function getMainContent();

}
