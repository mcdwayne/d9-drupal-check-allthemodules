<?php

namespace Drupal\core_extend;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Provides a repository for config entities with conditions.
 */
class ConfigEntityConditionRepository {

  use ConditionAccessResolverTrait;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   *
   * @todo maybe remove this. Unsure how to cache, other than possible mapping.
   */
  protected $cacheFactory;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new ConfigEntityConditionRepository.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_factory
   *   The Organization cache backend.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The plugin context handler.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity-type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string $entity_type_id
   *   (optional) An entity-type id to set for storage.
   */
  public function __construct(CacheBackendInterface $cache_factory, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, $entity_type_id = '') {
    $this->cacheFactory = $cache_factory;
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
    $this->entityTypeId = $entity_type_id;
    $this->moduleHandler = $module_handler;

    if (!empty($entity_type_id) && $entity_type_manager->hasHandler($entity_type_id, 'storage')) {
      $this->storage = $entity_type_manager->getStorage($entity_type_id);
    }
  }

  /**
   * Loaded config entities with conditions.
   *
   * @return \Drupal\core_extend\Entity\ConfigEntityConditionInterface[]|\Drupal\Core\Entity\EntityInterface[]
   *   The loaded config entities.
   */
  protected function loadEntities() {
    return $this->storage->loadMultiple();
  }

  /**
   * Retrieves context mapping for a context-aware plugin.
   *
   * @param \Drupal\Core\Plugin\ContextAwarePluginInterface $plugin
   *   The context-aware plugin.
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   An array of contexts, keyed by plugin context slot key.
   */
  protected function getContextMapping(ContextAwarePluginInterface $plugin, array $contexts = []) {
    // Use runtime contexts if context is empty.
    $runtime_contexts = (empty($contexts));

    // Mapping is on plugin. Return loaded runtime contexts.
    if ($runtime_contexts && !empty($plugin->getContextMapping())) {
      return $this->contextRepository->getRuntimeContexts(array_values($plugin->getContextMapping()));;
    }

    // Get all available contexts to match required contexts.
    if ($runtime_contexts) {
      $contexts = $this->contextRepository->getAvailableContexts();
    }

    $mapping = [];
    foreach ($plugin->getContextDefinitions() as $context_slot => $definition) {
      $matches = $this->contextHandler->getMatchingContexts($contexts, $definition);

      // Replace matches with runtime values.
      if ($runtime_contexts) {
        $matches = $this->contextRepository->getRuntimeContexts(array_keys($matches));
      }

      // Place in context slot for contexts with set values.
      foreach ($matches as $match_id => $match) {
        if (!is_null($match->getContextData()->getValue())) {
          $mapping[$context_slot] = $match;
          break;
        }
      }
    }

    return $mapping;
  }

  /**
   * Load config entities based on a passed-in entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The contextual entity to match against loaded config entities.
   *
   * @return \Drupal\core_extend\Entity\ConfigEntityConditionInterface[]|\Drupal\Core\Entity\EntityInterface[]
   *   The matched config.
   */
  protected function getConfigByEntity(EntityInterface $entity) {
    // Create an optional context definition for organization entities.
    $context_definition = new ContextDefinition('entity:' . $entity->getEntityTypeId(), $entity->getEntityType()->getLabel(), FALSE);
    $contexts[$entity->getEntityTypeId()] = new Context($context_definition, $entity);

    // Allow other modules to attach related contexts based on current entity.
    $this->moduleHandler->alter([
      'config_entity_condition_contexts',
      "config_{$this->entityTypeId}_condition_contexts",
    ], $contexts, $entity);

    // @todo cache this based on passed-in entity & user or serialized contexts?

    return $this->getConfigByContext($contexts);
  }

  /**
   * Load config entities based on a passed-in contexts.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   (optional) An array of contexts. If empty, defaults to any available
   *   contexts. @todo could allow it back-fill additional available contexts.
   *
   * @return \Drupal\core_extend\Entity\ConfigEntityConditionInterface[]|\Drupal\Core\Entity\EntityInterface[]
   *   The matched config.
   */
  protected function getConfigByContext(array $contexts = []) {
    $config = [];

    // Validate each config entity before adding to available config array.
    foreach ($this->loadEntities() as $entity_id => $entity) {
      // Run through conditions if any are set.
      if ($entity->getConditions()->count() > 0) {
        $conditions = [];
        foreach ($entity->getConditions()->getIterator() as $condition_id => $condition) {
          // Compile conditions.
          try {
            $mapping = $this->getContextMapping($condition, $contexts);
            $this->contextHandler->applyContextMapping($condition, $mapping);
            $conditions[] = $condition;
          }
          // Skip role if it doesn't match every condition.
          catch (ContextException $e) {
            continue 2;
          }
        }
        // Skip this entity if contexts don't apply.
        if ($this->resolveConditions($conditions, 'and') === FALSE) {
          continue;
        }
      }

      // Add to available config.
      $config[$entity_id] = $entity;
    }

    return $config;
  }

}
