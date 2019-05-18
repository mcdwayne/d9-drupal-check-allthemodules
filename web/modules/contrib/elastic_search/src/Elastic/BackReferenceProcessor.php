<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 16.05.17
 * Time: 10:57
 */

namespace Drupal\elastic_search\Elastic;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BackreferenceProcessor
 *
 * @package Drupal\elastic_search\Elastic
 *
 * Based on https://gist.github.com/grayside/a7b8aba74ccf36ff984b0b9499b3a188
 * //TODO - must respect map depth
 */
class BackReferenceProcessor implements ContainerInjectionInterface {

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  /**
   * Entity Field Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Array of all fields that can reference the given entity/bundle.
   *
   * @var array
   */
  protected $backReferenceFields = [];

  /**
   * Stores an array of entity IDs that are backreferencing.
   * The values of the array are always entity ids.
   * The keys will be revision ids if the entity supports revision and entity ids if not.
   *
   * @var array
   */
  protected $backReferenceEntityIds = [];

  const CID_SEPARATOR = '__';

  /**
   * Constructs a \Drupal\back_reference\BackReferenceFinder
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   Entity Field Query.
   * @param \Drupal\Core\Entity\EntityTypeManager  $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   Entity Field Manager.
   */
  public function __construct(QueryFactory $entity_query,
                              EntityTypeManager $entity_type_manager,
                              EntityFieldManager $entity_field_manager) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * @inheritDoc
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'),
                      $container->get('entity_type.manager'),
                      $container->get('entity_field.manager'));
  }

  /**
   * Load all entities that reference the entity of the given identifier.
   *
   * This method does not perform any checks on the targeted entity to verify
   * the applicability of the field, as those are assumed to have been handled
   * as part of deriving a confirmed FieldConfig object.
   *
   * @param \Drupal\field\Entity\FieldConfig $field
   *   The field definition from which we derive query conditions.
   * @param string                           $target_id
   *   The entity identifier the reference field should target. $entity->id()
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of Entities that reference our specified target ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @todo Expand for workflow/workspace if and when those elements are added.
   */
  public function loadReferencingEntities(FieldConfig $field, string $target_id): array {

    $ids = $this->getReferencingEntities($field, $target_id);
    return $this->entityTypeManager->getStorage($field->getTargetEntityTypeId())
                                   ->loadMultiple($ids);
  }

  public function getReferencingEntities(FieldConfig $field, $target_id): array {
    $cid = $field->getTargetEntityTypeId() . self::CID_SEPARATOR . $field->getName() . self::CID_SEPARATOR . $target_id;

    if (!isset($this->backReferenceEntityIds[$cid])) {

      /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
      $query = $this->entityQuery->get($field->getTargetEntityTypeId(), 'AND')
                                 ->condition($field->getName(), $target_id);

      // Check if Field Type is publishable.
      if ($statusField = $this->getStatusFieldFromFieldType($field)) {
        $query->condition($statusField, 1);
      }

      $this->backReferenceEntityIds[$cid] = $query->execute();
    }
    return $this->backReferenceEntityIds[$cid];
  }

  /**
   * Retrieves every reference field which can point at the current entity.
   *
   * @param string $entity_type_id
   *   The machine name identifier for the entity type.
   * @param string $entity_bundle_id
   *   The machine name identifier for the entity bundle. Defaults to NULL.
   *
   * @return \Drupal\field\Entity\FieldConfig[]
   *   Array of all applicable fields keyed by field name.
   *
   * @todo Determine if we need specific fields instead of all fields. If so,
   *   add a array $mask = [] parameter as whitelist.
   */
  public function referencingFields(string $entity_type_id, string $entity_bundle_id): array {
    $cid = $entity_type_id . self::CID_SEPARATOR . $entity_bundle_id;
    if (isset($this->backReferenceFields[$cid])) {
      return $this->backReferenceFields[$cid];
    }
    $entity_reference_fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    $fields = [];
    foreach ($entity_reference_fields as $entity_type_field_is_on => $field_info) {
      /**
       * @var string  $field_name
       * @var mixed[] $field_data
       */
      foreach ($field_info as $field_name => $field_data) {
        /** @var string $entity_bundle_field_is_on */
        foreach ($field_data['bundles'] as $entity_bundle_field_is_on) {
          /* @var \Drupal\field\Entity\FieldConfig */
          $field = FieldConfig::loadByName($entity_type_field_is_on, $entity_bundle_field_is_on, $field_name);
          // Check to see if the field is applicable to our entity and check for
          // references if so.
          if ($field && static::referenceFieldAppliesToEntity($field, $entity_type_id, $entity_bundle_id)) {
            $fields[$field_name] = $field;
          }
        }
      }
    }
    $this->backReferenceFields[$cid] = $fields;
    return $this->backReferenceFields[$cid];
  }

  /**
   * Identifies if supplied field is applicable to the given entity.
   *
   * @param \Drupal\field\Entity\FieldConfig $field
   *   The field configuration we are testing.
   * @param string                           $entity_type_id
   *   The machine name identifier for the entity type.
   * @param string                           $entity_bundle_id
   *   The machine name identifier for the entity bundle. Defaults to NULL.
   *
   * @return bool
   *   TRUE if the field is applicable, FALSE otherwise.
   */
  public static function referenceFieldAppliesToEntity(FieldConfig $field,
                                                       $entity_type_id,
                                                       $entity_bundle_id = NULL): bool {
    $entity_type_targeted_by_field = $field->getSetting('target_type');
    $field_handler = $field->getSetting('handler_settings');
    return $entity_type_targeted_by_field === $entity_type_id &&
           isset($field_handler['target_bundles']) &&
           isset($field_handler['target_bundles'][$entity_bundle_id]);
  }

  /**
   * Check if Field Type is publishable.
   *
   * @param \Drupal\field\Entity\FieldConfig $field
   *   The field definition from which we derive query conditions.
   *
   * @return bool|string
   */
  public function getStatusFieldFromFieldType(FieldConfig $field) {
    try {
      $entityType = $this->entityTypeManager->getDefinition($field->getTargetEntityTypeId());
      return $entityType->getKey('published');
    } catch (PluginNotFoundException $e) {
      return FALSE;
    }
  }

}