<?php

namespace Drupal\entity_reference_integrity;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity reference integrity entity handler.
 */
class EntityReferenceIntegrityEntityHandler implements EntityHandlerInterface, EntityReferenceDependencyManagerInterface {

  /**
   * A referential field map.
   *
   * @var \Drupal\entity_reference_integrity\DependencyFieldMapGeneratorInterface
   */
  protected $fieldMap;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Create an EntityReferenceDependencyManager.
   */
  public function __construct(DependencyFieldMapGeneratorInterface $fieldMap, EntityTypeManagerInterface $entityTypeManager) {
    $this->fieldMap = $fieldMap;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_reference_integrity.field_map'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function hasDependents(EntityInterface $entity) {
    $referencing_fields = $this->fieldMap->getReferencingFields($entity->getEntityTypeId());
    foreach ($referencing_fields as $source_entity_type_id => $source_fields) {
      // Select a count of all entities of type $source_entity_type_id which
      // have any one of their entity reference fields pointing to the given
      // entity.
      $number_of_referencing_entities = $this->referentialEntityQuery($source_entity_type_id, $source_fields, $entity->id())
        ->count()
        ->execute();

      // Stop immediately if we find any foreign entity which references our own
      // entity.
      if ($number_of_referencing_entities > 0) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentEntityIds(EntityInterface $entity) {
    $referencing_entities = [];
    $referencing_fields = $this->fieldMap->getReferencingFields($entity->getEntityTypeId());
    foreach ($referencing_fields as $source_entity_type_id => $source_fields) {
      $entity_ids = array_values($this->referentialEntityQuery($source_entity_type_id, $source_fields, $entity->id())->execute());
      if (!empty($entity_ids)) {
        $referencing_entities[$source_entity_type_id] = $entity_ids;
      }
    }
    return $referencing_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentEntities(EntityInterface $entity) {
    $entities = $this->getDependentEntityIds($entity);
    $loaded_entities = [];
    foreach ($entities as $entity_type_id => $ids) {
      $loaded_entities[$entity_type_id] = array_values($this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($ids));
    }
    return $loaded_entities;
  }

  /**
   * Start an entity query for entities matching set conditions.
   *
   * Query for all entities of the specified type which have any fields in the
   * source field list that match the given target ID.
   *
   * @param string $entity_type
   *   The entityt type.
   * @param array $source_fields
   *   An array of source fields.
   * @param string|int $target_id
   *   The target ID to search for.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A query object.
   */
  protected function referentialEntityQuery($entity_type, array $source_fields, $target_id) {
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
    $or_group = $query->orConditionGroup();
    foreach ($source_fields as $source_field) {
      $or_group->condition($source_field, $target_id, '=');
    }
    $query->condition($or_group);
    return $query;
  }

}
