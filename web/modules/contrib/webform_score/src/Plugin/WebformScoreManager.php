<?php

namespace Drupal\webform_score\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages webform score plugins.
 *
 * @see hook_webform_score_info_alter()
 * @see \Drupal\webform_score\Annotation\WebformScore
 * @see \Drupal\webform_score\Plugin\WebformScorInterface
 * @see plugin_api
 */
class WebformScoreManager extends DefaultPluginManager implements WebformScoreManagerInterface {

  /**
   * Constructor for WebformScoreManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/WebformScore', $namespaces, $module_handler, 'Drupal\webform_score\Plugin\WebformScoreInterface', 'Drupal\webform_score\Annotation\WebformScore');

    $this->alterInfo('webform_score_info');
    $this->setCacheBackend($cache_backend, 'webform_score_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function pluginOptionsCompatibleWith($data_type_id, $include_aggregation = TRUE) {
    $options = [];

    // TODO: Try to take into consideration OOP inheritance when building the
    // list of options. For example if $data_type_id is "string", then not only
    // "string" data type but also all of its children should be matched, such
    // as "email", "uri", etc.
    foreach ($this->getDefinitions() as $definition) {
      $is_data_type_compatible = in_array($data_type_id, $definition['compatible_data_types']) || in_array('*', $definition['compatible_data_types']);
      $is_aggregation_compatible = $include_aggregation || !$definition['is_aggregation'];
      if ($is_data_type_compatible && $is_aggregation_compatible) {
        $options[$definition['id']] = $definition['label'];
      }
    }

    return $options;
  }

}
