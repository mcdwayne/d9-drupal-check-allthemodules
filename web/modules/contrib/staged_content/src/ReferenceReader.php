<?php

namespace Drupal\staged_content;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Helper service to detect all the reference from a content entity.
 */
class ReferenceReader {

  /**
   * The references to index.
   *
   * @var array
   *   All the references to index for this type.
   */
  protected $includedEntityReferenceTypes = ['paragraph', 'media', 'file'];

  /**
   * Find all the references connected to a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to find the references for.
   * @param array $includedReferenceItems
   *   All the entity types that should be indexed as connected references.
   *
   * @return array
   *   An array of all the entities keyed by their uuid.
   */
  public function detectReferencesRecursively(ContentEntityInterface $entity, array $includedReferenceItems = []) {
    $this->includedEntityReferenceTypes = $includedReferenceItems;

    $indexedEntities = [$entity->uuid() => $entity];
    $references = $this->getEntityReferencesRecursive($entity, 0, $indexedEntities);

    return $references;
  }

  /**
   * Returns all referenced entities of an entity.
   *
   * This method is also recursive to support use-cases like a node -> media
   * -> file.
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
  protected function getEntityReferencesRecursive(ContentEntityInterface $entity, $depth = 0, array &$indexed_dependencies = []) {
    $entity_dependencies = $entity->referencedEntities();

    foreach ($entity_dependencies as $dependent_entity) {
      // Config entities should not be exported but rather provided by default
      // config.
      if (!($dependent_entity instanceof ContentEntityInterface)) {
        continue;
      }

      if (!in_array($dependent_entity->getEntityTypeId(), $this->includedEntityReferenceTypes)) {
        continue;
      }
      // Using UUID to keep dependencies unique to prevent recursion.
      $key = $dependent_entity->uuid();
      if (isset($indexed_dependencies[$key])) {
        // Do not add already indexed dependencies.
        continue;
      }
      $indexed_dependencies[$key] = $dependent_entity;

      // @TODO improve logging.
      echo '    ';
      for ($i = 0; $i < $depth; $i++) {
        echo '--';
      }
      echo 'Attached ' . $dependent_entity->getEntityTypeId() . ':' . $dependent_entity->uuid() . "\n";

      // Build in some support against infinite recursion.
      if ($depth < 6) {
        // @todo Make $depth configurable.
        $indexed_dependencies += $this->getEntityReferencesRecursive($dependent_entity, $depth + 1, $indexed_dependencies);
      }
    }

    return $indexed_dependencies;
  }

}
