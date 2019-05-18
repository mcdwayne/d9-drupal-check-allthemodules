<?php

namespace Drupal\contentserialize;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * A general helper class for Content Serialization.
 *
 * @todo Make this class specific to dependency resolution.
 */
class Utility {

  /**
   * Returns all referenced entities of an entity.
   *
   * This method is also recursive to support use-cases like a node -> media
   * -> file.
   *
   * Copied from \Drupal\default_content\Exporter::getEntityReferencesRecursive().
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param int $depth
   *   Guard against infinite recursion.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $indexed_dependencies
   *   Previously discovered dependencies.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Keyed array of entities indexed by entity type and ID.
   */
  public static function referencedEntitiesRecursive(ContentEntityInterface $entity, $depth = 0, array &$indexed_dependencies = []) {
    $entity_dependencies = $entity->referencedEntities();

    foreach ($entity_dependencies as $dependent_entity) {
      // Config entities should not be exported but rather provided by default
      // config.
      if (!($dependent_entity instanceof ContentEntityInterface)) {
        continue;
      }
      // Using UUID to keep dependencies unique to prevent recursion.
      $key = $dependent_entity->uuid();
      if (isset($indexed_dependencies[$key])) {
        // Do not add already indexed dependencies.
        continue;
      }
      $indexed_dependencies[$key] = $dependent_entity;
      // Build in some support against infinite recursion.
      if ($depth < 6) {
        // @todo Make $depth configurable.
        $indexed_dependencies += static::referencedEntitiesRecursive($dependent_entity, $depth + 1, $indexed_dependencies);
      }
    }

    return $indexed_dependencies;
  }

  /**
   * Yield a flat list of entities and their dependencies.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   The entities to export along with their dependencies.
   *
   * @return \Generator|ContentEntityInterface[]
   */
  public static function enumerateEntitiesAndDependencies($entities) {
    foreach ($entities as $entity) {
      yield $entity;
      foreach (static::referencedEntitiesRecursive($entity) as $dependency) {
        yield $dependency;
      }
    }
  }

}