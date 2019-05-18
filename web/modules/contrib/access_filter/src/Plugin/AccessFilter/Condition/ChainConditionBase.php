<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Condition;

use Drupal\access_filter\Plugin\AccessFilterPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for condition plugins that chains other conditions.
 */
abstract class ChainConditionBase extends ConditionBase {

  /**
   * The access filter condition plugin manager.
   *
   * @var \Drupal\access_filter\Plugin\AccessFilterPluginManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * Creates a new PathCondition object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\access_filter\Plugin\AccessFilterPluginManagerInterface $condition_plugin_manager
   *   The access filter condition plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessFilterPluginManagerInterface $condition_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->conditionPluginManager = $condition_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.access_filter.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $summary = '<ul>';
    foreach ($this->configuration['conditions'] as $condition) {
      $instance = $this->createPluginInstance($condition);
      if ($instance) {
        $summary .= '<li>' . $instance->getPluginId() . ': ' . $instance->summary() . '</li>';
      }
    }
    $summary .= '</ul>';
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfiguration(array $configuration) {
    $errors = [];

    foreach ($configuration['conditions'] as $condition) {
      $instance = $this->createPluginInstance($condition);
      if ($instance) {
        $errors = array_merge($errors, $instance->validateConfiguration($condition));
      }
      else {
        $errors[] = $this->t("Condition type '@type' does not exist.", ['@type' => $condition['type']]);
      }
    }

    return $errors;
  }

  /**
   * Creates plugin instance.
   *
   * @param array $condition
   *   The array containing condition data.
   *
   * @return \Drupal\access_filter\Plugin\ConditionInterface|bool
   *   Plugin instance or FALSE if failed.
   */
  protected function createPluginInstance(array $condition) {
    $plugins = $this->conditionPluginManager->getDefinitions();
    $plugin_id = $condition['type'];
    if (isset($plugins[$plugin_id])) {
      return $this->conditionPluginManager->createInstance($plugin_id, $condition);
    }
    return FALSE;
  }

}
