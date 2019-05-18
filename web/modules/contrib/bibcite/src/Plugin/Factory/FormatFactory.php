<?php

namespace Drupal\bibcite\Plugin\Factory;

use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Factory for bibcite format plugin.
 */
class FormatFactory extends ContainerFactory {

  /**
   * {@inheritdoc}
   */
  public static function getPluginClass($plugin_id, $plugin_definition = NULL, $required_interface = NULL) {
    // Only one class for all plugins.
    return '\Drupal\bibcite\Plugin\BibciteFormat';
  }

}
