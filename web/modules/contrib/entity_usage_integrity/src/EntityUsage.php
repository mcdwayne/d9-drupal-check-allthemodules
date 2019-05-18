<?php

namespace Drupal\entity_usage_integrity;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_usage\EntityUsageInterface;
use Drupal\entity_usage\EntityUsageTrackManager;

/**
 * Defines entity usage service for entity usage integrity module.
 *
 * Difference between Drupal\entity_usage\EntityUsage
 * and Drupal\entity_usage_integrity\EntityUsage is:
 *  - Entity Usage Integrity needs data for default revision of source
 *    and original listTargets() and listSources() are not supporting that,
 *  - on entity save, we have to get data based on entity fields references
 *    to validate, as this data is not saved yet to entity_usage table
 *    in database.
 */
final class EntityUsage {

  /**
   * The database connection used to store entity usage information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity usage track manager.
   *
   * @var \Drupal\entity_usage\EntityUsageTrackManager
   */
  protected $entityUsageTrackManager;

  /**
   * The entity usage service.
   *
   * @var \Drupal\entity_usage\EntityUsageInterface
   */
  protected $entityUsage;

  /**
   * The name of the SQL table used to store entity usage information.
   *
   * @var string
   */
  const TABLE_NAME = 'entity_usage';

  /**
   * Construct the EntityUsage object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the entity usage
   *   information.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_usage\EntityUsageInterface $entity_usage
   *   The entity usage service.
   * @param \Drupal\entity_usage\EntityUsageTrackManager $entity_usage_track_manager
   *   The entity usage track manager.
   */
  public function __construct(Connection $connection, EntityTypeManager $entity_type_manager, EntityUsageInterface $entity_usage, EntityUsageTrackManager $entity_usage_track_manager) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityUsage = $entity_usage;
    $this->entityUsageTrackManager = $entity_usage_track_manager;
  }

  /**
   * Provide a list of all referencing source entities for a target entity.
   *
   * This method has been provided, because on revisionable content,
   * data returned by Drupal\entity_usage\EntityUsage::listSources()
   * may give wrong integrity validation results.
   * For revisionable content, Drupal\entity_usage\EntityUsage::listSources()
   * will return all source revisions, which are referring to current target.
   * But we only need relations between current target revision and
   * default source revision. So we have to filter original call of
   * Drupal\entity_usage\EntityUsage::listSources().
   * This method also returns data in different format, than
   * Drupal\entity_usage\EntityUsage::listSources(), but it is assumed that
   * it is useful only for the module and won't be used anywhere else.
   *
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   A target entity.
   *
   * @return array
   *   A nested array with usage data. The first level is keyed by the type of
   *   the source entities. The value is array contains source entity ids
   *   that uses reference to the target entity.
   */
  public function listDefaultRevisionsForSources(EntityInterface $target_entity) {
    $references = [];
    // Get all sources.
    $sources = $this->entityUsage->listSources($target_entity);
    // Extra filtering for revisionable sources.
    foreach ($sources as $source_type => $source_type_entities) {
      foreach ($source_type_entities as $source_id => $source_revisions) {
        if (isset($references[$source_type][$source_id])) {
          // Relation between target and default source revision was confirmed
          // earlier and it is attached in response, no need to check again.
          continue;
        }

        // Load source entity default revision.
        $source = $this->entityTypeManager->getStorage($source_type)->load($source_id);

        if ($source->getEntityType()->isRevisionable()) {
          // $source_revisions is list of all revisions of source, which
          // are referring to the target. We have to check, if DEFAULT revision
          // of source is is present on that list. If, yes, then this relation
          // should added for further processing (validation of entity usage
          // relation). If no, it means that DEFAULT revision of source has
          // no reference to target revision. It was attached in original
          // response, because one or more of older revisions has that
          // reference. But entity usage integrity checks integrity only
          // for default revision, so further processing makes no
          // sense in that scenario.
          /** @var \Drupal\Core\Entity\RevisionableInterface $source */
          foreach ($source_revisions as $entity_usage_source_revision) {
            if ($source->getRevisionId() == $entity_usage_source_revision['source_vid']) {
              $references[$source_type][$source_id] = $source_id;
              // Stop further checking, as we just added current entity.
              break;
            }
          }
        }
        else {
          $references[$source_type][$source_id] = $source_id;
        }
      }
    }
    return $references;
  }

  /**
   * Provide a list of all referenced target entities for a source entity.
   *
   * This method has been provided, because on revisionable content,
   * data returned by Drupal\entity_usage\EntityUsage::listTargets()
   * may give wrong integrity validation results.
   * For revisionable content, Drupal\entity_usage\EntityUsage::listTargets()
   * will return all target references in all source revisions.
   * But we only need to get targets for default source revision.
   * As original Drupal\entity_usage\EntityUsage::listTargets()
   * is not supporting that, we have to wrote new method.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity to check for references.
   *
   * @return array
   *   A nested array with usage data. The first level is keyed by the type of
   *   the target entities. The value is array contains target entity ids
   *   that are referenced by source entity.
   */
  public function listDefaultRevisionsForTargets(EntityInterface $source_entity) {
    $references = [];
    // If entity supports revisions, get targets for current revision.
    if ($source_entity->getEntityType()->isRevisionable()) {
      // Entities can have string IDs. We support that by using different
      // columns on each case.
      $source_id_column = $this->isInt($source_entity->id()) ? 'source_id' : 'source_id_string';
      $query = $this->connection->select(self::TABLE_NAME, 'e');
      $query
        ->fields('e', [
          'target_id',
          'target_id_string',
          'target_type',
        ])
        ->condition($source_id_column, $source_entity->id())
        ->condition('source_type', $source_entity->getEntityTypeId())
        ->condition('count', 0, '>')
        ->condition('source_vid', $source_entity->getRevisionId());
      $query
        ->orderBy('target_id', 'DESC');

      $result = $query->execute();

      foreach ($result as $usage) {
        $target_id_value = !empty($usage->target_id) ? $usage->target_id : (string) $usage->target_id_string;
        $references[$usage->target_type][$target_id_value] = $target_id_value;
      }
    }
    else {
      $targets = $this->entityUsage->listTargets($source_entity);
      foreach ($targets as $target_type => $type_targets) {
        foreach ($type_targets as $target_id => $type_targets_data) {
          $references[$target_type][$target_id] = $target_id;
        }
      }
    }

    return $references;
  }

  /**
   * Get candidates to become entity usage targets for given source.
   *
   * Before entity save, new relations are not present on entity usage
   * table and we can't get them this way (like on listTargets())
   * We have to get new relations directly from referencing fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity before save to check for references.
   *
   * @return array
   *   A nested array with usage data. The first level is keyed by the type of
   *   the target entities. The value is array contains target entities ids
   *   that are referenced by source entity.
   */
  public function listDefaultRevisionsForTargetsFromFields(EntityInterface $source_entity) {
    // TODO this method return e.g. references to the config entities.
    //   We have to fix that and return only Content Entity References.
    //   One example is 'type' field which has referenceto Config Entity
    //   EntityType e.g. node_type|article.
    $references = [];
    $definitions = $this->entityUsageTrackManager->getDefinitions();
    foreach ($definitions as $definition) {
      $instance = $this->entityUsageTrackManager->createInstance($definition['id']);
      $trackable_field_types = $instance->getApplicableFieldTypes();
      $fields = array_keys($instance->getReferencingFields($source_entity, $trackable_field_types));
      foreach ($fields as $field_name) {
        if ($source_entity instanceof FieldableEntityInterface && $source_entity->hasField($field_name) && !$source_entity->{$field_name}->isEmpty()) {
          /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
          foreach ($source_entity->{$field_name} as $field_item) {
            $properties = $field_item->getProperties();
            // Fix for dynamic entity references where entity_type stores
            // string like entity_type:bundle, where we need entity_type.
            if (isset($properties['target_type']) && $field_item->get('target_type')) {
              $type = $field_item->get('target_type')->getValue();
              if (strpos($type, ':') !== FALSE) {
                $field_item = clone $field_item;
                $field_item->set('target_type', substr($type, 0, strpos($type, ':')));
              }
            }
            // The entity is being created with value on this field, so we
            // just need to add a tracking record.
            $target_entities = $instance->getTargetEntities($field_item);
            foreach ($target_entities as $target_entity) {
              list($target_type, $target_id) = explode("|", $target_entity);
              $references[$target_type][$target_id] = $target_id;
            }
          }
        }
      }
    }
    return $references;
  }

  /**
   * Check if a value is an integer, or an integer string.
   *
   * Core doesn't support big integers (bigint) for entity reference fields.
   * Therefore we consider integers with more than 10 digits (big integer) to be
   * strings.
   *
   * @param int|string $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is a numeric integer or a string containing an integer,
   *   FALSE otherwise.
   *
   * @see https://www.drupal.org/project/entity_usage/issues/2989033
   * @see https://www.drupal.org/project/drupal/issues/2680571
   */
  protected function isInt($value) {
    return ((string) (int) $value === (string) $value) && strlen($value) < 11;
  }

}
