<?php

namespace Drupal\ad_entity\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides the manager for Advertising context plugins and context data.
 */
class AdContextManager extends DefaultPluginManager {

  /**
   * An array holding a collection of backend context data.
   *
   * Various implementations of Advertising types may
   * apply the collected context data via backend.
   *
   * @var array
   */
  protected $contextData;

  /**
   * A list of instantiated context plugins.
   *
   * @var \Drupal\ad_entity\Plugin\AdContextInterface[]
   */
  protected $contextPlugins;

  /**
   * An array holding previously collected context data.
   *
   * @var array
   */
  protected $previousContextData;

  /**
   * An array of entities which are involved to provide Advertising context.
   *
   * @var array
   */
  protected $involvedEntities;

  /**
   * An array of previously involved entities which provide Advertising context.
   *
   * @var array
   */
  protected $previouslyInvolvedEntities;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterManager;

  /**
   * Constructor method.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   The field formatter plugin manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, FormatterPluginManager $formatter_manager) {
    parent::__construct('Plugin/ad_entity/AdContext', $namespaces, $module_handler, 'Drupal\ad_entity\Plugin\AdContextInterface', 'Drupal\ad_entity\Annotation\AdContext');
    $this->alterInfo('ad_entity_adcontext');
    $this->setCacheBackend($cache_backend, 'ad_entity_adcontext');

    $this->entityTypeManager = $entity_type_manager;
    $this->formatterManager = $formatter_manager;

    $this->previousContextData = [];
    $this->previouslyInvolvedEntities = [];
    $this->setContextData([]);
    $this->setInvolvedEntities([]);
  }

  /**
   * Loads a context plugin instance.
   *
   * In contrast to ::createInstance(), this method
   * makes use of in-memory caching for instances.
   * Context plugins usually don't require configuration,
   * so it should be fine to reuse already created instances.
   *
   * @param string $plugin_id
   *   The ID of the context plugin.
   * @param array $configuration
   *   (Optional) Bypasses in-memory caching if not empty.
   *
   * @return \Drupal\ad_entity\Plugin\AdContextInterface
   *   The instance of the context plugin.
   */
  public function loadContextPlugin($plugin_id, array $configuration = []) {
    if (empty($configuration)) {
      if (!isset($this->contextPlugins[$plugin_id])) {
        $this->contextPlugins[$plugin_id] = $this->createInstance($plugin_id);
      }
      return $this->contextPlugins[$plugin_id];
    }
    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * Adds backend context data to the current data collection.
   *
   * @param string $plugin_id
   *   The plugin id of the context.
   * @param array $settings
   *   (Optional) An array of corresponding settings for the context.
   * @param array $apply_on
   *   (Optional) An array of Advertising entity ids where to apply the context.
   *   When empty, the context can be applied on all available ads.
   */
  public function addContextData($plugin_id, array $settings = [], array $apply_on = []) {
    $this->contextData[$plugin_id][] = [
      'settings' => $settings,
      'apply_on' => $apply_on,
    ];
  }

  /**
   * Informs the manager that this entity has provided Advertising context.
   *
   * Other components might need to know which entities were involved
   * during the delivering of Advertising context.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity which has provided Advertising context.
   */
  public function addInvolvedEntity(EntityInterface $entity) {
    $this->involvedEntities[$entity->getEntityTypeId()][$entity->id()] = $entity;
  }

  /**
   * Check whether the entity is involved for providing Advertising context.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check for.
   *
   * @return bool
   *   TRUE if entity is known to be involved, FALSE otherwise.
   */
  public function entityIsInvolved(EntityInterface $entity) {
    if (isset($this->involvedEntities[$entity->getEntityTypeId()][$entity->id()])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns a list of backend context data for the given Advertising entity id.
   *
   * The result does not include data of third party providers.
   * If you want to have third party settings included,
   * use AdEntity::getContextData() instead.
   *
   * @param string $ad_entity_id
   *   The id (machine name) of the Advertising entity.
   *
   * @return array
   *   The list of available backend context data for the Advertising entity.
   */
  public function getContextDataForEntity($ad_entity_id) {
    $available = [];

    foreach ($this->contextData as $plugin_id => $data_items) {
      foreach ($data_items as $data) {
        if (empty($data['apply_on']) || in_array($ad_entity_id, $data['apply_on'])) {
          $available[$plugin_id][] = $data['settings'];
        }
      }
    }

    return $available;
  }

  /**
   * Returns a list of backend context data belonging to the context plugin id.
   *
   * @param string $plugin_id
   *   The context plugin id.
   *
   * @return array
   *   The list of backend context data belonging to the context plugin.
   */
  public function getContextDataForPlugin($plugin_id) {
    if (!empty($this->contextData[$plugin_id])) {
      return $this->contextData[$plugin_id];
    }
    return [];
  }

  /**
   * Returns a list of context data for given plugin and Advertising entity id.
   *
   * The result does not include data of third party providers.
   * If you want to have third party settings included,
   * use AdEntity::getContextDataForPlugin() instead.
   *
   * @param string $plugin_id
   *   The context plugin id.
   * @param string $ad_entity_id
   *   The id (machine name) of the Advertising entity.
   *
   * @return array
   *   The list of available context data for the plugin and Advertising entity.
   */
  public function getContextDataForPluginAndEntity($plugin_id, $ad_entity_id) {
    $available = [];

    if (!empty($this->contextData[$plugin_id])) {
      foreach ($this->contextData[$plugin_id] as $data) {
        if (empty($data['apply_on']) || in_array($ad_entity_id, $data['apply_on'])) {
          $available[] = $data['settings'];
        }
      }
    }

    return $available;
  }

  /**
   * Get the whole collection of backend context data.
   *
   * @return array
   *   The current backend context data collection.
   */
  public function getContextData() {
    return $this->contextData;
  }

  /**
   * Returns all known entities which are involved for providing Ad context.
   *
   * @return array
   *   All known involved entities, keyed by entity type and id.
   */
  public function getInvolvedEntities() {
    return $this->involvedEntities;
  }

  /**
   * Resets the context for the entity from the given route match (when given).
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function resetContextDataForRoute(RouteMatchInterface $route_match) {
    // Although this is also done in ::resetContextDataForEntity(),
    // it should be made sure that the reset to the previous state
    // won't result in context data loss.
    $this->previousContextData = $this->contextData;
    $this->previouslyInvolvedEntities = $this->involvedEntities;

    foreach ($route_match->getParameters() as $param) {
      if ($param instanceof EntityInterface) {
        $this->resetContextDataForEntity($param);
        return;
      }
    }

    $this->setContextData([]);
    $this->setInvolvedEntities([]);
  }

  /**
   * Resets the backend context data for the given entity.
   *
   * This might be useful when displaying the given entity with ads,
   * which should only have context corresponding to this entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to reset the context data.
   */
  public function resetContextDataForEntity(EntityInterface $entity) {
    // Memorize the current state of the collected data,
    // for being able to revert back to it later.
    $this->previousContextData = $this->contextData;
    $this->previouslyInvolvedEntities = $this->involvedEntities;
    // Reset the collected context data.
    $this->setContextData([]);
    $this->setInvolvedEntities([]);
    // Allow other modules to react on the reset of the context data.
    $this->moduleHandler->invokeAll('ad_context_data_reset', [$this, $entity]);
  }

  /**
   * Resets the collected context data to a previous state.
   *
   * This method undos the last call either of
   * ::resetContextDataForEntity() or ::resetContextDataForRoute(),
   * with any other subsequent additions or changes to the collected data.
   */
  public function resetToPreviousContextData() {
    $this->contextData = $this->previousContextData;
    $this->involvedEntities = $this->previouslyInvolvedEntities;
  }

  /**
   * Collects backend context data provided by the fields of the given entity.
   *
   * Any data found will be added to the collection
   * managed by the AdContextManager.
   *
   * The data will be fetched from the Advertising context fields.
   * If and how Advertising context is being delivered, depends on the
   * (already configured) display options of the entity's fields.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity from which to fetch context data.
   * @param string|array $display_options
   *   (Optional) Can be either the name of a view mode which has properly
   *   configured field formatters for the Advertising context fields,
   *   or an array of display settings.
   *   See EntityViewBuilderInterface::viewField() for more information.
   */
  public function collectContextDataFrom(FieldableEntityInterface $entity, $display_options = 'default') {
    $context_fields = [];
    foreach ($entity->getFieldDefinitions() as $definition) {
      if ($definition->getType() == 'ad_entity_context') {
        $context_fields[$definition->getName()] = $definition;
      }
    }

    if (is_string($display_options)) {
      // Fetch the configured display options for this view mode.
      $display_storage = $this->entityTypeManager->getStorage('entity_view_display');
      /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
      $display = $display_storage->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $display_options);
      foreach ($context_fields as $field_name => $definition) {
        if ($configured_options = $display->getComponent($field_name)) {
          $configured_options['settings']['appliance_mode'] = 'backend';
          $configured_options['field_definition'] = $definition;
          $configured_options['view_mode'] = $display_options;
          /** @var \Drupal\Core\Field\FormatterInterface $formatter */
          $formatter = $this->formatterManager
            ->createInstance($configured_options['type'], $configured_options);
          if (($item_list = $entity->get($field_name)) && ($language = $entity->language())) {
            $formatter->viewElements($item_list, $language->getId());
          }
        }
      }
    }
    else {
      if (empty($display_options['settings']['appliance_mode'])) {
        $display_options['settings']['appliance_mode'] = 'backend';
      }
      foreach ($context_fields as $field_name => $definition) {
        /** @var \Drupal\Core\Field\FormatterInterface $formatter */
        $formatter = $this->formatterManager
          ->createInstance($display_options['type'], $display_options);
        if (($item_list = $entity->get($field_name)) && ($language = $entity->language())) {
          $formatter->viewElements($item_list, $language->getId());
        }
      }
    }
  }

  /**
   * Set the current collection of backend context data.
   *
   * @param array $context_data
   *   The backend context data collection.
   */
  public function setContextData(array $context_data) {
    $this->contextData = $context_data;
  }

  /**
   * Set the list of known entities which have provided Advertising context.
   *
   * @param array $involved
   *   An array of involved entities, keyed by entity type and id.
   */
  public function setInvolvedEntities(array $involved) {
    $this->involvedEntities = $involved;
  }

}
