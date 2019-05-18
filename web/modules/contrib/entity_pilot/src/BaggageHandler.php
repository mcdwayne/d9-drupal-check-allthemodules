<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_pilot\Event\CalculateDependenciesEvent;
use Drupal\entity_pilot\Event\EntityPilotEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A service for handling entity baggage (dependencies).
 */
class BaggageHandler implements BaggageHandlerInterface {

  /**
   * Array of static dependency calculations, keyed by entity type and ID.
   *
   * @var array
   */
  protected $static;

  /**
   * Stack of dependencies being sought, to prevent circular references.
   *
   * @var array
   */
  protected $stack;

  /**
   * Cache storage for calculated dependencies.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Constructs a new \Drupal\entity_pilot\BaggageHandler object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache storage.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   Event dispatcher.
   */
  public function __construct(CacheBackendInterface $cache, EventDispatcherInterface $dispatcher) {
    $this->cache = $cache;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(EntityInterface $entity, array &$tags = []) {
    $cid = sprintf('ep__%s__%s', $entity->getEntityTypeId(), $entity->id());
    $this->stack[$cid] = $cid;
    if (!isset($this->static[$cid])) {
      // Static-cache miss - try persistent cache.
      if ($loaded = $this->cache->get($cid)) {
        $this->static[$cid] = $loaded->data;
      }
      else {
        $this_tags = [
          sprintf('ep__%s__%s', $entity->getEntityTypeId(), $entity->id()),
        ];
        $tags = array_merge($tags, $this_tags);
        $dependencies = [];
        foreach ($entity->referencedEntities() as $dependent_entity) {
          if ($dependent_entity instanceof ConfigEntityInterface) {
            continue;
          }
          $dependencies[$dependent_entity->uuid()] = $dependent_entity;
          $child_cid = sprintf('ep__%s__%s', $dependent_entity->getEntityTypeId(), $dependent_entity->id());
          if (!isset($this->static[$child_cid]) && empty($this->stack[$child_cid])) {
            // Traverse and find dependencies of the dependant.
            $dependencies = $dependencies + $this->calculateDependencies($dependent_entity, $this_tags);
          }
        }
        // Don't allow circular references to deposit references to the original
        // entity.
        unset($dependencies[$entity->uuid()]);
        // Allow other modules to extend the dependencies.
        $event = new CalculateDependenciesEvent($entity, $dependencies, $tags);
        /** @var \Drupal\entity_pilot\Event\CalculateDependenciesEvent $result */
        $result = $this->dispatcher->dispatch(EntityPilotEvents::CALCULATE_DEPENDENCIES, $event);
        $dependencies = $result->getDependencies();
        $tags = array_unique(array_merge($tags, $event->getTags()));
        // Statically cache the result.
        $this->static[$cid] = $dependencies;
        $this->cache->set($cid, $dependencies, Cache::PERMANENT, $this_tags);
      }
    }
    unset($this->stack[$cid]);
    return $this->static[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->static = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function generateFieldMap(array $entities) {
    $map = [];
    foreach ($entities as $entity) {
      foreach ($entity->getFieldDefinitions() as $field_name => $field) {
        $map[$entity->getEntityTypeId()][$field_name] = $field->getType();
      }
    }
    return $map;
  }

}
