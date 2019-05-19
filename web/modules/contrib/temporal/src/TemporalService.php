<?php
/**
 * @file
 * Contains \Drupal\temporal\TemporalService.
 */

namespace Drupal\temporal;

use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;


/**
 * Class TemporalService
 * Provides base properties, methods for building queries against the temporal data
 * Can also be used to construct one off or complex queries that the supporting classes
 * don't handle
 *
 * @package Drupal\temporal
 */
class TemporalService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var string
   */
  protected $sort;

  /**
   * @var string
   */
  protected $sort_direction;

  /**
   * @var array
   */
  protected $queryFields;

  /**
   * @var int
   */
  protected $delta;

  /**
   * @var boolean
   */
  protected $status;

  /**
   * Constructor that sets some intial defaults
   *
   * @param \Drupal\Core\Database\Connection $connection
   *  The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection     = $connection;
    $this->delta          = 0;
    $this->status         = 1;
    $this->sort           = 'created'; // Utilize the created date as primary sort to preserve historal order
    $this->sort_direction = 'ASC';

    // Supply a default list of fields for result sets
    $this->queryFields = [
      //'id',
      'entity_id',
      'value',
      'created',
      'entity_type',
      'entity_field',
      //'entity_bundle',
      //'entity_field_type',
      //'user_id',
      //'changed',
    ];
  }

  /**
   * Initializes the query property. Having this explicit step allows for changes to other
   * properties like the field list or conditions before constructing the query.
   *
   * @param string $alias
   *
   * Making the alias option here allows for cleaner queries, but also providing query
   * control for complex queries
   */
  protected function startQuery() {
    return $this->connection->select('temporal', 't');
  }

  /**
   * Inject the queryField array of fields to the query
   * Note that this should be called after adding other fields to the object
   *
   * @param string $alias
   * @param \Drupal\Core\Database\Query\Select $query
   */
  protected function injectFields($query) {
    $query->fields('t', $this->queryFields);
  }

  /**
   * Helper method to add base conditions limiting results to active and zero delta values only
   * @param \Drupal\Core\Database\Query\Select $query
   */
  protected function injectDeltaStatusConditions($query) {
    $query->condition('delta', $this->delta);   // Only grab the zero value of multivalue fields
    $query->condition('status', $this->status);  // Only grab active entries
  }

  /**
   * @param \Drupal\Core\Database\Query\Select $query
   */
  protected function addDefaultSort($query) {
    $query->orderBy($this->sort, $this->sort_direction);
  }

  /**
   * Helper for adding fields to the queryFields list
   * Adds the field to the list as a keyed value of same to allow removal later
   *
   * @param string $field
   */
  protected function addField($field) {
    $this->queryFields[$field] = $field;
  }

  /**
   * Updates the value type to match the entity_field_type
   * This allows the storage of all data types as strings to be
   * recasted to their original values when results are returned
   *
   * @param array $results
   * @param boolean $cleanup
   *
   * @return array
   */
  protected function applyFieldType($results, $cleanup) {
    foreach ($results AS $index => $result) {
      if($result['entity_field_type']) {
        $cast = temporal_field_type_mapping($results[$index]['entity_field_type']);
        settype($results[$index]['value'], $cast);
        if($cleanup) {
          unset($results[$index]['entity_field_type']);
        }
      }
    }
    return $results;
  }

  /**
   * Executes the query, applies casting to the value, returns the results
   *
   * @param \Drupal\Core\Database\Query\Select $query
   * @return array
   */
  public function getResults($query) {
    if(is_a($query, '\Drupal\Core\Database\Query\Select')) {
      // Add in the entity_field_type field so we can cast the value
      if(!in_array('entity_field_type', $this->queryFields)) {
        $this->addField('entity_field_type');
        $cleanup = true;
      }
      else {
        // Allow for inclusion of the entity_field_type as a return field
        $cleanup = false;
      }

      // Add in the list of fields before fetching results if not already populated
      if(!count($query->getFields()) > 0) {
        $this->injectFields($query);
      }
      $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      if (count($results) > 0) {
        $results = $this->applyFieldType($results, $cleanup);
      }
      // Remove the entity_field_type field if we added it in
      if($cleanup) {
        unset($this->queryFields['entity_field_type']);
      }

      return $results;
    }
    else {
      return NULL;
    }
  }

  /**
   * Master method to load, process, apply and mark future values as completed
   *
   * @return int
   */
  public function performFutureValueActions() {
    $future_values = NULL;
    $future_value_entries = $this->getFutureValues();
    $processed_values = $this->processFutureValues($future_value_entries);
    $results = $this->applyFutureValues($processed_values);

    foreach ($future_value_entries AS $index => $future_value_entry) {
      $future_values[] = $future_value_entry['id'];
    }
    if(count($future_values) > 0) {
      // Update the rows in the temporal table, removing the future flag
      $query = $this->connection->update('temporal');
      $query->fields(['future' => 0]);
      $query->condition('id', $future_values, 'IN');
      $query->execute();
    }

    return $results;
  }

  /**
   * Service to select future values that are to be applied to entities
   *
   * @return array
   */
  private function getFutureValues() {
    // End of today
    $day_end = mktime(23, 59, 59);

    // Select all temporal entries from the past till now that are marked as future, allow multiple delta results
    $query = $this->startQuery();
    $this->addField('id');
    $this->addField('delta');
    $this->addField('entity_bundle');
    $this->addField('entity_field_type');
    $this->injectFields($query);

    $query->condition('status', 1)
      ->condition('future', 1)
      ->condition('created', $day_end, '<=');
    // Ensure all results are ordered by nid, and then delta so reconstituting them is done correctly
    $query->orderBy('entity_type');
    $query->orderBy('entity_id');
    $query->orderBy('created');
    $query->orderBy('delta');
    $query->orderBy('id', 'DESC');

    return $this->getResults($query);
  }

  /**
   * Group all entries by entity type and entity_id so entities with more than one change can be applied in one operation
   *
   * @param array $temporal_entries
   * @return array
   */
  private function processFutureValues($temporal_entries) {
    $entries = [];

    foreach($temporal_entries AS $key => $entry) {
      switch($entry['entity_field_type']) {
        case 'boolean':
          // Need to cast the booleans back to integer values
          $value = $entry['value'] ? 1 : 0;
          break;

        case 'entity_reference':
          $value = ['target_id' => $entry['value']];
          break;

        default:
          $value = $entry['value'];
      }
      $entries[$entry['entity_type']][$entry['entity_id']][$entry['entity_field']][$entry['delta']] = $value;
    }

    return $entries;
  }

  /**
   * Apply the values from a list of temporal entries to their respective entities
   * @param array $entries
   * @return int
   */
  private function applyFutureValues($entries) {
    $modified = 0;
    $entity = NULL;

    foreach($entries AS $entity_type => $entity_list) {
      foreach($entity_list AS $entity_id => $entity_fields) {
        $message = '';
        if ($entity_type == 'user') {
          /** @var User $entity */
          $entity = \Drupal\user\Entity\User::load($entity_id);
        }
        if ($entity_type == 'node') {
          /** @var Node $entity */
          $entity = \Drupal\node\Entity\Node::load($entity_id);
        }

        // Apply field changes
        foreach ($entity_fields AS $entity_field => $values) {
          $entity->set($entity_field, $values);
          $modified++;
        }
        if($modified) {
          // Mark entity as future update to prevent temporal_entity_presave from acting
          $entity->temporal_bypass = TRUE;
          // Save entity, record in an array that the entity_id future update was successful
          $entity->save();
          // Log changes made
          $context = [
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'title' => $entity_type == 'user' ? $entity->getAccountName() : $entity->getTitle(),
            'values' => print_r($entity_fields, true),
          ];
          $logger = \Drupal::logger('temporal');
          $logger->info('Temporal Future Values Updated for [{entity_type}:{entity_id} | {title}] with Values: {values}', $context);
        }
      }
    }

    return $modified;
  }

  /**
   * Getters and Setters
   */

  /**
   * @return boolean
   */
  public function isStatus() {
    return $this->status;
  }

  /**
   * @param boolean $status
   */
  public function setStatus($status) {
    $this->status = $status;
  }

  /**
   * @return string
   */
  public function getSort() {
    return $this->sort;
  }

  /**
   * @param string $sort
   */
  public function setSort($sort) {
    $this->sort = $sort;
  }

  /**
   * @return string
   */
  public function getSortDirection() {
    return $this->sort_direction;
  }

  /**
   * @param string $sort_direction
   */
  public function setSortDirection($sort_direction) {
    $this->sort_direction = $sort_direction;
  }
}