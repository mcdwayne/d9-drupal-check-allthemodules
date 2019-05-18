<?php

/**
 * @file
 * Contains \Drupal\mpac\Plugin\Type\SelectionPluginManager.
 */

namespace Drupal\mpac\Plugin\Type;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\ReflectionFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\mpac\Plugin\Type\Selection\SelectionBroken;

/**
 * Plugin type manager for the Multi-path autocomplete Selection plugin.
 */
class SelectionPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    $this->discovery = new AnnotatedClassDiscovery('Plugin/mpac/selection', $namespaces, 'Drupal\mpac\Annotation\MpacSelection');

    // We're not using the parent constructor because we use a different factory
    // method and don't need the derivative discovery decorator.
    $this->factory = new ReflectionFactory($this);

    $this->alterInfo($module_handler, 'mpac_selection');
    $this->setCacheBackend($cache_backend, $language_manager, 'mpac_selection');
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::getInstance().
   */
  public function getInstance(array $options) {
    $type = $options['type'];

    // Get all available selection plugins for this entity type.
    $selection_handler_groups = $this->getSelectionGroups($type);

    // Sort the selection plugins by weight and select the best match.
    uasort($selection_handler_groups, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
    end($selection_handler_groups);
    $plugin_id = key($selection_handler_groups);

    if ($plugin_id) {
      return $this->createInstance($plugin_id, $options);
    }
    else {
      return new SelectionBroken();
    }
  }

  /**
   * Returns a list of selection plugins that can provide autocomplete results.
   *
   * @param string $type
   *   A Multi-path autocomplete type.
   *
   * @return array
   *   An array of selection plugins grouped by selection group.
   */
  public function getSelectionGroups($type) {
    $plugins = array();

    foreach ($this->getDefinitions() as $plugin_id => $plugin) {
      if (!isset($plugin['types']) || in_array($type, $plugin['types'])) {
        $plugins[$plugin_id] = $plugin;
      }
    }

    return $plugins;
  }
}
