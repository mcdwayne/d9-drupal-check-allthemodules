<?php

namespace Drupal\entity_graph;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Class EntityGraph.
 */
class EntityGraph implements EntityGraphInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityFieldManager definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * EntityGraph constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityFieldManager $entity_field_manager, ModuleHandlerInterface $moduleHandler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraphNodeWithNeighbourhood($entity, $matcher = NULL, $maxDepth = 5) {
    static $initialMaxDepth = NULL;
    if (is_null($initialMaxDepth)) {
      $initialMaxDepth = $maxDepth;
    }
    $isInitialCall = $initialMaxDepth === $maxDepth;
    $conditionMatched = is_callable($matcher) && call_user_func($matcher, $entity);
    $depthLimitReached = $maxDepth === 0;

    $graphNode = new EntityGraphNode($entity);

    if ((!$isInitialCall && $conditionMatched) || $depthLimitReached) {
      // Condition matched or depth limit reached. Create a graph node without
      // any further traversal. Don't do it for the first iteration though.
      return $graphNode;
    }

    $incomingEdges = [];
    foreach ($this->getReferencingEntities($entity) as $relatedEntity) {
      $sourceGraphNode = $this->getGraphNodeWithNeighbourhood($relatedEntity, $matcher, $maxDepth - 1);
      $incomingEdges[] = new EntityGraphEdge($sourceGraphNode, $graphNode);
    }

    $graphNode->setIncomingEdges($incomingEdges);

    return $graphNode;
  }

  /**
   * Returns the list of field type ids considered to be link fields.
   *
   * @return string[]
   */
  public function getLinkFieldTypes() {
    // TODO: Handle this via edge plugins.
    $types = ['link'];
    $this->moduleHandler->alter('entity_graph_link_field_types', $types);
    return $types;
  }

  /**
   * Returns the list of field type ids considered to be reference fields.
   *
   * @return string[]
   */
  public function getReferenceFieldTypes() {
    // TODO: Handle this via edge plugins.
    $types = ['entity_reference', 'entity_reference_revisions'];
    $this->moduleHandler->alter('entity_graph_reference_field_types', $types);
    return $types;
  }

  /**
   * Return list of entities directly referencing given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return EntityInterface[]
   *   Array of parent entities.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getReferencingEntities(EntityInterface $entity) {
    $entities = [];
    $referenceFields = $this->getReferenceFields($entity->getEntityTypeId());
    $linkFields = $this->getAllLinkFields();
    $allEntityTypes = array_unique(array_merge(array_keys($referenceFields), array_keys($linkFields)));
    $uris = $this->getAllUris($entity);
    foreach ($allEntityTypes as $referencingEntityType) {
      $query = $this
        ->entityTypeManager
        ->getStorage($referencingEntityType)
        ->getQuery('OR');
      if (isset($referenceFields[$referencingEntityType])) {
        foreach ($referenceFields[$referencingEntityType] as $field) {
          $query->condition($field, $entity->id());
        }
      }
      if (isset($linkFields[$referencingEntityType])) {
        foreach ($linkFields[$referencingEntityType] as $field) {
          foreach ($uris as $uri) {
            $query->condition("$field.uri", $uri, 'ENDS_WITH');
          }
        }
      }
      // TODO: Add sorting if possible
      $result = $query->execute();
      if (!empty($result)) {
        $entities = array_merge($entities, $this
          ->entityTypeManager
          ->getStorage($referencingEntityType)
          ->loadMultiple($result)
        );
      }
    }
    return $entities;
  }

  /**
   * Returns the list of all possible uris we know of for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return string[]
   */
  protected function getAllUris(EntityInterface $entity) {
    $entityType = $entity->getEntityTypeId();
    $uris = [
      "entity:$entityType/{$entity->id()}",
    ];

    // TODO: Add regular urls and path aliases to entities that have canonical links.

    if ($entityType == 'media') {
      $sourceField = $entity->getType()->getConfiguration()['source_field'];
      if ($entity->hasField($sourceField)) {
        $fileEntities = $entity->get($sourceField)->referencedEntities();
        if (is_array($fileEntities)) {
          foreach ($fileEntities as $fileEntity) {
            $uris[] = $fileEntity->getFileUri();
            // In addition to the stream uri, get an absolute path to file.
            $absoluteUrl = file_create_url($fileEntity->getFileUri());
            $uris[] = $absoluteUrl;
            // Finally, get a relative path to. These are widely used because
            // they work across the environments.
            $parts = parse_url($absoluteUrl);
            $uris[] = $parts['path'];
            $uris[] = "internal:$parts[path]";
          }
        }
      }
    }

    return $uris;
  }

  /**
   * Returns fields that reference given entity type.
   *
   * @param string $targetEntityType
   *   Entity type id.
   *
   * @return string[]
   *   List of field ids keyed by entity type.
   */
  protected function getReferenceFields($targetEntityType) {
    static $referenceFields = [];
    if (!isset($referenceFields[$targetEntityType])) {
      foreach ($this->entityTypeManager->getDefinitions() as $entityType => $definition) {
        if ($definition->isSubclassOf(FieldableEntityInterface::class)) {
          /** @var FieldDefinitionInterface $fieldStorage */
          foreach ($this->entityFieldManager->getFieldStorageDefinitions($entityType) as $fieldStorage) {
            if (
              $fieldStorage instanceof FieldStorageConfigInterface
              && in_array($fieldStorage->getType(), $this->getReferenceFieldTypes())
              && $fieldStorage->getSetting('target_type') == $targetEntityType
            ) {
              $referenceFields[$targetEntityType][$entityType][] = $fieldStorage->getName();
            }
          }
        }
      }
    }
    return $referenceFields[$targetEntityType];
  }

  /**
   * Return the list of all the link fields in the system grouped by the entity
   * type.
   *
   * @return array
   */
  protected function getAllLinkFields() {
    static $linkFields = [];
    if (empty($linkFields)) {
      foreach ($this->entityTypeManager->getDefinitions() as $entityType => $definition) {
        if ($definition->isSubclassOf(FieldableEntityInterface::class)) {
          /** @var FieldDefinitionInterface $fieldStorage */
          foreach ($this->entityFieldManager->getFieldStorageDefinitions($entityType) as $fieldStorage) {
            if (
              $fieldStorage instanceof FieldStorageConfigInterface
              && in_array($fieldStorage->getType(), $this->getLinkFieldTypes())
            ) {
              $linkFields[$entityType][] = $fieldStorage->getName();
            }
          }
        }
      }
    }
    return $linkFields;
  }

}
