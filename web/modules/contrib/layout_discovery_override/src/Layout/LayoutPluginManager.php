<?php

namespace Drupal\layout_discovery_override\Layout;

use Drupal\Core\Layout\LayoutPluginManager as CoreLayoutPluginManager;
use Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;

/**
 * Provides a plugin manager for layouts. Extends the core LayoutPluginManager.
 */
class LayoutPluginManager extends CoreLayoutPluginManager {

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {

    if (!$this->discovery) {
      $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);

      // Do not look for layouts in the core layout_discovery module.
      $module_directories = $this->moduleHandler->getModuleDirectories();
      unset($module_directories['layout_discovery']);

      $discovery = new YamlDiscoveryDecorator($discovery, 'layouts', $module_directories + $this->themeHandler->getThemeDirectories());
      $discovery = new AnnotationBridgeDecorator($discovery, $this->pluginDefinitionAnnotationName);
      $discovery = new DerivativeDiscoveryDecorator($discovery);
      $this->discovery = $discovery;
    }
    return $this->discovery;
  }

}
