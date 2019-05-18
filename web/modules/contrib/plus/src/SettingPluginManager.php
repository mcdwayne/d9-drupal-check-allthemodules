<?php

namespace Drupal\plus;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\plus\Annotation\Setting;
use Drupal\plus\Plugin\PluginProviderTypeInterface;
use Drupal\plus\Plugin\Setting\SettingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manages discovery and instantiation of "Setting" annotations.
 *
 * @ingroup plugins_setting
 */
class SettingPluginManager extends ProviderPluginManager {

  /**
   * Constructs a new \Drupal\plus\Plugin\SettingManagerProvider object.
   *
   * @param \Drupal\plus\Plugin\PluginProviderTypeInterface $provider_type
   *   The plugin provider type used for discovery.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The backend cache service to use.
   */
  public function __construct(PluginProviderTypeInterface $provider_type, CacheBackendInterface $cache_backend) {
    parent::__construct($provider_type, 'Plugin/Setting', SettingInterface::class, Setting::class, $cache_backend);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.providers'),
      $container->get('cache.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions($sorted = TRUE) {
    $definitions = parent::getDefinitions(FALSE);
    if ($sorted) {
      $groups = [];
      foreach ($definitions as $plugin_id => $definition) {
        $key = !empty($definition['groups']) ? implode(':', array_keys($definition['groups'])) : '_default';
        $groups[$key][$plugin_id] = $definition;
      }
      ksort($groups);
      $definitions = [];
      foreach ($groups as $settings) {
        uasort($settings, [$this, 'sort']);
        $definitions = array_merge($definitions, $settings);

      }
    }
    return $definitions;
  }

  /**
   * Sorts a structured array by either a set 'weight' property or by the ID.
   *
   * @param array $a
   *   First item for comparison.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public static function sort(array $a, array $b) {
    if (isset($a['weight']) || isset($b['weight'])) {
      return SortArray::sortByWeightElement($a, $b);
    }
    else {
      return SortArray::sortByKeyString($a, $b, 'id');
    }
  }

}
