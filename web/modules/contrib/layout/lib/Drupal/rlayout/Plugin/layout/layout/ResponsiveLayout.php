<?php

/**
 * @file
 * Definition of Drupal\rlayout\Plugin\layout\layout\ResponsiveLayout.
 */

namespace Drupal\rlayout\Plugin\layout\layout;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\layout\Plugin\LayoutInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * @Plugin(
 *  id = "responsive_layout",
 *  derivative = "Drupal\rlayout\Plugin\Derivative\Layout"
 * )
 */
class ResponsiveLayout extends PluginBase implements LayoutInterface {

  /**
   * Overrides Drupal\Component\Plugin\PluginBase::__construct().
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery) {
    // Get definition by discovering the declarative information.
    $definition = $discovery->getDefinition($plugin_id);
    foreach ($definition['regions'] as $region => $title) {
      if (!isset($configuration['regions'][$region])) {
        $configuration['regions'][$region] = array();
      }
    }
    parent::__construct($configuration, $plugin_id, $discovery);
  }

  /**
   * Implements Drupal\layout\Plugin\LayoutInterface::renderLayout().
   */
  public function renderLayout($admin = FALSE) {
    $definition = $this->getDefinition();
    return '@todo Temporary';
  }
}
