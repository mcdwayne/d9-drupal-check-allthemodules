<?php

/**
 * @file
 * Contains \Drupal\draggable_blocks\Plugin\Draggable\DraggablePluginManager.
 */

namespace Drupal\draggable_blocks\Plugin\Draggable;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;

/**
 * Plugin type manager for all draggables.
 */
class DraggablePluginManager extends DefaultPluginManager implements DraggablePluginManagerInterface {


  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a DraggableBlocksPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handle to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $plugin_interface = 'Drupal\draggable_blocks\Plugin\Draggable\DraggableInterface';
    $plugin_definition_annotation_name = 'Drupal\draggable_blocks\Annotation\Draggable';
    parent::__construct("Plugin/Draggable", $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);
    $discovery = $this->getDiscovery();
    //dpm($discovery);
    //dpm($module_handler->getModuleDirectories() + $theme_handler->getThemeDirectories());
    $this->discovery = new YamlDiscoveryDecorator($discovery, 'draggable.blocks', $module_handler->getModuleDirectories() + $theme_handler->getThemeDirectories());
    $this->themeHandler = $theme_handler;
    
    $this->setCacheBackend($cache_backend, 'draggables');
    $this->alterInfo('draggables');
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {

  }

  /**
   * {@inheritdoc}
   */
  public function getRegions() {
    return $this->getDefinitions();
  }

}
