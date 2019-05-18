<?php

namespace Drupal\elastic_search\Plugin;

/**
 * Class ElasticAbstractFieldFactory.
 *
 * Gets the appropriate plugin of type ElasticAbstractField.
 *
 * Based on the declared field_types in the plugin, it retrieves the best
 * match taking also in account the weight.
 *
 * @package Drupal\elastic_search
 */
class ElasticAbstractFieldFactory {

  /**
   * The query plugin to use in case no one is found.
   */
  const DEFAULT_FALLBACK_PLUGIN = '';

  /**
   * @var \Drupal\elastic_search\ElasticAbstractFieldManager
   */
  protected $elasticAbstractFieldManager;

  /**
   * ElasticAbstractFieldFactory constructor.
   *
   * @param \Drupal\elastic_search\Plugin\ElasticAbstractFieldManager $abstractFieldManager
   */
  public function __construct(ElasticAbstractFieldManager $abstractFieldManager) {
    $this->elasticAbstractFieldManager = $abstractFieldManager;
  }

  /**
   * @param string $fieldType
   *   The field type that the plugin supports (e.g. integer,
   *   custom_field_1..).
   * @param bool   $fallbackPlugin
   *   The plugin to fallback if none found.
   *
   * @return \Drupal\elastic_search\Plugin\ElasticAbstractField\ElasticAbstractFieldInterface|null
   *   The ElasticAbstractField plugin or null.
   */
  public function getAbstractFieldPlugin(string $fieldType,
                                         $fallbackPlugin = FALSE) {
    $pluginsByWeight = $this->getPluginsByWeight($fieldType);

    // Iterating through the ordered plugins.
    /** @var $plugin \Drupal\elastic_search\Plugin\ElasticAbstractField\ElasticAbstractFieldInterface */
    foreach ($pluginsByWeight as $plugin) {
      if (in_array($fieldType, $plugin->getFieldTypes())) {
        return $plugin;
      }
    }

    if ($fallbackPlugin &&
        $this->elasticAbstractFieldManager->hasDefinition(self::DEFAULT_FALLBACK_PLUGIN)) {
      return $this->elasticAbstractFieldManager->createInstance(self::DEFAULT_FALLBACK_PLUGIN);
    }

    return NULL;
  }

  /**
   * Returns the plugins based on the fieldType and ordered by weight.
   *
   * @return array
   */
  public function getPluginsByWeight(string $fieldType) {
    $pluginsByWeight = [];
    foreach ($this->elasticAbstractFieldManager->getDefinitions() as $pluginName => $pluginValue) {
      /** @var $plugin \Drupal\elastic_search\Plugin\ElasticAbstractField\ElasticAbstractFieldInterface */
      $plugin = $this->elasticAbstractFieldManager->createInstance($pluginName);
      if (in_array($fieldType, $plugin->getFieldTypes())) {
        $pluginsByWeight[$plugin->getWeight()] = $plugin;
      }
    }

    // Sort by weight.
    ksort($pluginsByWeight);

    return $pluginsByWeight;
  }

}
