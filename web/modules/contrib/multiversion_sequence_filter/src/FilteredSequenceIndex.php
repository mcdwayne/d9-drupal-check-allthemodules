<?php

namespace Drupal\multiversion_sequence_filter;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\crop\Entity\Crop;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\multiversion\Entity\Index\SequenceIndexInterface;
use Drupal\multiversion\MultiversionManagerInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;

/**
 * A sequence index supporting filter values and handling additions.
 *
 * Reference to content entities are added in as additions (without recursion).
 */
class FilteredSequenceIndex implements SequenceIndexInterface {

  /**
   * Additional entities settings.
   *
   * An array containing arrays, with two keys:
   *  - allowed_entity_types
   *  - excluded_fields
   *
   * @var array[]
   */
  protected $referenceAdditionSettings;

  /**
   * @var array[]
   */
  protected $filterValuesCondition = [[], []];

  /**
   * @var string[]
   */
  protected $types = [];

  /**
   * @var int
   */
  protected $workspaceId;

  /**
   * @var \Drupal\multiversion_sequence_filter\SequenceIndexStorage
   */
  protected $indexStorage;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\multiversion\MultiversionManagerInterface
   */
  protected $multiversionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates the object.
   *
   * @param \Drupal\multiversion_sequence_filter\SequenceIndexStorage $indexStorage
   *   The sequence index storage.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspaceManager
   *   The workspace manager.
   * @param \Drupal\multiversion\MultiversionManagerInterface $multiversionManager
   *   The multiversion manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The multiversion sequence filter settings.
   */
  public function __construct(SequenceIndexStorage $indexStorage, WorkspaceManagerInterface $workspaceManager, MultiversionManagerInterface $multiversionManager, EntityTypeManagerInterface $entityTypeManager, ImmutableConfig $config) {
    $this->indexStorage = $indexStorage;
    $this->workspaceManager = $workspaceManager;
    $this->multiversionManager = $multiversionManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->referenceAdditionSettings = $config->get('added_references');
  }

  /**
   * {@inheritdoc}
   */
  public function useWorkspace($id) {
    $this->workspaceId = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function add(ContentEntityInterface $entity) {
    if ($entity->getEntityType()->get('workspace') === FALSE) {
      // Entities without a workspace are unsupported.
      return;
    }

    // Only index the default revision.
    // @see Issue #3027816.
    if (!$entity->isDefaultRevision()) {
      return;
    }

    $name = $entity->getEntityTypeId() . ':' . $entity->id();
    $record = $this->buildRecord($entity);

    // @see \Drupal\multiversion_sequence_filter\SequenceIndexStorage::addMultiple()
    $this->indexStorage->addMultiple($this->getWorkspaceId(), [
      $name => [
        'seq' => $record['seq'],
        'value' => $record,
        'type' => $entity->getEntityTypeId() . '.' . $entity->bundle(),
        'filter_values' => $this->getFilterValues($entity),
        'additional_entries' => $this->getAdditionalEntries($entity),
      ],
    ]);
  }

  /**
   * Sets the entity type and bundle condition for getting ranges.
   *
   * @param string[] $types
   *   The types to filter for; i.e., each being an entity type or an
   *   "entity_type.bundle" combination.
   *
   * @return $this
   */
  public function addTypeCondition(array $types) {
    $this->types = $types;
    return $this;
  }

  /**
   * Sets the filter values to use for getting ranges.
   *
   * @param string[] $types
   *   The types ("entity_type.bundle" combinations) that should be filtered.
   * @param string[] $filterValues
   *   The values to filter for.
   *
   * @return $this
   */
  public function addFilterValuesCondition(array $types, array $filterValues) {
    $this->filterValuesCondition = [$types, $filterValues];
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @see ::setFilterValues()
   */
  public function getRange($start, $stop = NULL, $inclusive = TRUE, $limit = NULL) {
    list($filtered_types, $filter_values) = $this->filterValuesCondition;
    return $this->indexStorage->getRange($this->getWorkspaceId(), $start, $stop, $this->types, $filtered_types, $filter_values, $inclusive, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSequenceId() {
    return $this->indexStorage->getLastEntry($this->getWorkspaceId());
  }

  /**
   * Gets the workspace ID to use.
   *
   * @param int $workspace_id
   *   (optional) The workspace ID of an entity.
   *
   * @return int
   */
  protected function getWorkspaceId($workspace_id = NULL) {
    if (!$workspace_id) {
      $workspace_id = $this->workspaceId ?: $this->workspaceManager->getActiveWorkspaceId();
    }
    return $workspace_id;
  }

  /**
   * Builds the record to save with a sequence entry.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity revision.
   *
   * @return array
   *   The record.
   */
  protected function buildRecord(ContentEntityInterface $entity) {
    return [
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'entity_uuid' => $entity->uuid(),
      'revision_id' => $entity->getRevisionId(),
      'deleted' => $entity->_deleted->value,
      'rev' => $entity->_rev->value,
      'seq' => $this->multiversionManager->newSequenceId(),
      'local' => (boolean) $entity->getEntityType()->get('local'),
      'is_stub' => (boolean) $entity->_rev->is_stub,
    ];
  }

  /**
   * Gets the filter values for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity revision.
   *
   * @return string[]
   *   The filter values.
   */
  protected function getFilterValues(ContentEntityInterface $entity) {
    // @todo: Use the configured filter value plugin, e.g. inject it.
    return \Drupal::service('plugin.manager.replication_filter')
      ->createInstance('contentpool')
      ->deriveFilterValues($entity);
  }

  /**
   * Gets additional entries for the given entity.
   *
   * We do not handle recursion here as it would be hard to keep the index
   * updated correctly. Thus only the first level is supported.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity revision.
   *
   * @return string[]
   *   The names of additional entries.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAdditionalEntries(ContentEntityInterface $entity) {
    $additions = [];

    $allowed_entity_types = array_flip($this->referenceAdditionSettings['allowed_entity_types']);
    $excluded_fields = array_flip($this->referenceAdditionSettings['excluded_fields']);

    foreach ($entity->getFieldDefinitions() as $name => $definition) {
      if ($entity->get($name) instanceof EntityReferenceFieldItemListInterface) {
        $property = $definition->getItemDefinition()->getPropertyDefinition('entity');
        if ($property instanceof DataReferenceDefinitionInterface) {
          $target = $property->getTargetDefinition();
          /** @var \Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface $target */
          // Only include content entities that are white-listed.
          $entity_type = $this->entityTypeManager->getDefinition($target->getEntityTypeId());
          if ($entity_type instanceof ContentEntityTypeInterface && isset($allowed_entity_types[$target->getEntityTypeId()]) && !isset($excluded_fields[$name])) {
            foreach ($entity->get($name) as $item) {
              if ($item->entity) {
                $entry = $item->entity->getEntityTypeId() . ':' . $item->entity->id();
                $additions[$entry] = $entry;

                // Add special support for the crop entity of focal point.
                if ($item instanceof ImageItem && isset($allowed_entity_types['crop'])) {
                  $file = $item->entity;
                  if ($crop = Crop::findCrop($file->getFileUri(), 'focal_point')) {
                    $entry = $crop->getEntityTypeId() . ':' . $crop->id();
                    $additions[$entry] = $entry;
                  }
                }
              }
            }
          }
        }
      }
    }
    return $additions;
  }

}
