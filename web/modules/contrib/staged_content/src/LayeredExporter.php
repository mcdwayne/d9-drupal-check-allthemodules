<?php

namespace Drupal\staged_content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\staged_content\Storage\StorageHandlerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * A service for handling import of default content.
 *
 * @todo throw useful exceptions
 */
class LayeredExporter {

  /**
   * Should the id's be included in the export.
   *
   * @var bool
   *   Should the id's be included to the export of the entity.
   */
  protected $includeId;

  /**
   * Storage handler interface.
   *
   * @var \Drupal\staged_content\Storage\StorageHandlerInterface
   *   The storage handler.
   */
  protected $storageHandler;

  /**
   * Array of all the machine names of references that should be included.
   *
   * @var array
   *   All the entity machine names that should be included.
   */
  protected $includedEntityReferenceTypes = [];

  /**
   * The original entity type to export.
   *
   * @var string
   *   Entity type id for the original entities to export.
   */
  protected $originalEntityType;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The marker detection manager.
   *
   * @var \Drupal\staged_content\MarkerDetectorManagerInterface
   *   The plugin detection helper for drupal.
   */
  protected $markerDetectorManager;

  /**
   * Holds an interface to extract the marker for an entity.
   *
   * @var \Drupal\staged_content\Plugin\StagedContent\Marker\MarkerDetectorInterface
   *   The method of detecting the marker for this item.
   */
  protected $markerDetector;

  /**
   * Markers valid for detection of various pyramid sets of content.
   *
   * @var array
   */
  protected $markers = [];

  /**
   * Constructs the default content manager.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\staged_content\MarkerDetectorManagerInterface $markerDetectorManager
   *   Marker detection manager service.
   */
  public function __construct(Serializer $serializer, EntityTypeManagerInterface $entity_type_manager, MarkerDetectorManagerInterface $markerDetectorManager) {
    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
    $this->markerDetectorManager = $markerDetectorManager;
  }

  /**
   * {@inheritdoc}
   */
  public function exportType($entityTypeId, array $includedEntityReferenceTypes = [], $markers = [], $markerDetectorPluginId = 'label') {
    $this->markerDetector =
      $this->markerDetectorManager->createInstance($markerDetectorPluginId);

    $this->markers = $markers;
    $this->includedEntityReferenceTypes = $includedEntityReferenceTypes;
    $this->originalEntityType = $entityTypeId;

    // @TODO Prevent this from loading them all at once?
    $query = $this->entityTypeManager->getStorage($entityTypeId)->getQuery();
    $entityIds = $query->execute();

    // @TODO, improve output logging.
    echo 'Exporting ' . count($entityIds) . ' items' . "\n";

    foreach ($entityIds as $entityId) {
      $this->exportContent($entityTypeId, $entityId);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exportContent($entity_type_id, $entity_id) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity = $storage->load($entity_id);

    // Get all the items that are referenced.
    // Note that these are filtered based on the.
    $entities = [$entity->uuid() => $entity];
    $entities = $this->getEntityReferencesRecursive($entity, 0, $entities);

    foreach ($entities as $entity) {
      $this->exportSingleEntity($entity);
    }
  }

  /**
   * Handle the export of a single entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Handle a single entity.
   */
  protected function exportSingleEntity(ContentEntityInterface $entity) {
    $data = $this->serializer->serialize($entity,
      'storage_json',
      [
        'json_encode_options' => JSON_PRETTY_PRINT,
        'normalizer_options' => [
          'include_id' => $this->attachOriginalId($entity->getEntityTypeId()),
        ],
      ]
    );

    // Store the actual output based on the storage handler.
    if ($data !== 'null') {
      $marker = $this->markerDetector->detectMarker($entity, $this->markers);
      $this->storageHandler->storeData($data, $entity->getEntityTypeId(), $entity->uuid(), $marker);
    }
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

  /**
   * Should id's be included in the export.
   *
   * @param string $entityTypeId
   *   The id of the entity being handled.
   *
   * @return bool
   *   Should id's be included in the export.
   */
  public function attachOriginalId(string $entityTypeId) {
    return $this->includeId && $this->originalEntityType == $entityTypeId;
  }

  /**
   * Should id's be included in the export.
   *
   * @return bool
   *   Should id's be included in the export.
   */
  public function includeId() {
    return $this->includeId;
  }

  /**
   * Should id's be included in the export.
   *
   * @param bool $includeId
   *   Set or id's should be included in the export.
   */
  public function setIncludeId(bool $includeId) {
    $this->includeId = $includeId;
  }

  /**
   * Set the storage handler.
   *
   * @param \Drupal\staged_content\Storage\StorageHandlerInterface $storageHandler
   *   Storage handler for the output.
   */
  public function setStorageHandler(StorageHandlerInterface $storageHandler) {
    $this->storageHandler = $storageHandler;
  }

}
