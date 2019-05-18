<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages entity counter condition plugins.
 *
 * @see hook_entity_counter_condition_info_alter()
 * @see \Drupal\entity_counter\Plugin\EntityCounterConditionInterface
 * @see \Drupal\entity_counter\Plugin\EntityCounterConditionBase
 * @see \Drupal\entity_counter\Plugin\EntityCounterConditionManagerInterface
 * @see plugin_api
 */
class EntityCounterConditionManager extends DefaultPluginManager implements EntityCounterConditionManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ConditionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/EntityCounterCondition', $namespaces, $module_handler, 'Drupal\entity_counter\Plugin\EntityCounterConditionInterface', 'Drupal\entity_counter\Annotation\EntityCounterCondition');

    $this->alterInfo('entity_counter_condition_info');
    $this->setCacheBackend($cache_backend, 'entity_counter_condition_plugins');

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label', 'entity_type'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The condition "%s" must define the "%s" property.', $plugin_id, $required_property));
      }
    }

    $entity_type_id = $definition['entity_type'];
    if (!$this->entityTypeManager->getDefinition($entity_type_id)) {
      throw new PluginException(sprintf('The condition "%s" must specify a valid entity type, "%s" given.', $plugin_id, $entity_type_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFilteredDefinitions(array $entity_type_ids) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      // Filter by entity type.
      if (!in_array($definition['entity_type'], $entity_type_ids)) {
        unset($definitions[$plugin_id]);
        continue;
      }
    }

    // Sort by weigh and display label.
    uasort($definitions, function ($a, $b) {
      if ($a['weight'] == $b['weight']) {
        return strnatcasecmp($a['label'], $b['label']);
      }
      return ($a['weight'] < $b['weight']) ? -1 : 1;
    });

    return $definitions;
  }

}
