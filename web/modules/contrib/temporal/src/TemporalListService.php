<?php

/**
 * @file
 * Contains \Drupal\temporal\TemporalListService.
 */

namespace Drupal\temporal;
use Drupal\Core\Database\Connection;


/**
 * Class TemporalListService.
 *
 * Provides convenience methods to query the temporal data
 *
 * @package Drupal\temporal
 */
class TemporalListService extends TemporalService {
  /**
   * Constructor.
   * @param \Drupal\Core\Database\Connection $connection
   *  The database connection.
   */
  public function __construct(Connection $connection) {
    parent::__construct($connection);

  }

  /**
   * Defines a query of ALL data entries within an entity id list
   *
   * @param string $entity_type
   * @param string $entity_bundle
   * @param string $entity_field
   * @param array $entity_id_list
   *
   * @return \Drupal\Core\Database\Query\Select
   */
  public function prepareFieldValuesByEntityIdList($entity_type, $entity_bundle, $entity_field, $entity_id_list) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->startQuery();
    $this->injectDeltaStatusConditions($query);

    $query->condition('entity_type', $entity_type)
      ->condition('entity_bundle', $entity_bundle)
      ->condition('entity_field', $entity_field)
      ->condition('entity_id', $entity_id_list, 'IN');

    return $query;
  }

  /**
   * Defines a query of ALL data entries for a specfied entity id
   *
   * @param string $entity_type
   * @param string $entity_bundle
   * @param string $entity_field
   * @param int $entity_id
   *
   * @return \Drupal\Core\Database\Query\Select
   */
  public function prepareFieldValuesByEntityId($entity_type, $entity_bundle, $entity_field, $entity_id) {
    return $this->prepareFieldValuesByEntityIdList($entity_type, $entity_bundle, $entity_field, [$entity_id]);
  }

  /**
   * Defines a query of ALL data entries for a specfied field
   *
   * @param string $entity_type
   * @param string $entity_bundle
   * @param string $entity_field
   *
   * @return \Drupal\Core\Database\Query\Select
   */
  public function prepareFieldValuesByField($entity_type, $entity_bundle, $entity_field) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->startQuery();
    $this->injectDeltaStatusConditions($query);

    $query->condition('entity_type', $entity_type)
      ->condition('entity_bundle', $entity_bundle)
      ->condition('entity_field', $entity_field);

    return $query;
  }

  /**
   * Defines a query of ALL data entries for a specfied bundle
   * 
   * @param string $entity_type
   * @param string $entity_bundle
   *
   * @return \Drupal\Core\Database\Query\Select
   */
  public function prepareFieldValuesByBundle($entity_type, $entity_bundle) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->startQuery();
    $this->injectDeltaStatusConditions($query);

    $query->condition('entity_type', $entity_type)
      ->condition('entity_bundle', $entity_bundle);

    return $query;
  }

  /**
   * Defines a query of ALL data entries for a specfied entity type
   * 
   * @param string $entity_type
   *
   * @return \Drupal\Core\Database\Query\Select
   */
  public function prepareFieldValuesByEntityType($entity_type) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->startQuery();
    $this->injectDeltaStatusConditions($query);

    $query->condition('entity_type', $entity_type);

    return $query;
  }
  
  /**
   * Defines a query of ALL data entries for a specfied temporal type and entity_id
   * 
   * @param string $temporal_type
   * @param int $entity_id
   *
   * @return \Drupal\Core\Database\Query\Select
   */
  public function prepareFieldValuesByTemporalTypeEntityId($temporal_type, $entity_id) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->startQuery();
    $this->injectDeltaStatusConditions($query);

    $query->condition('type', $temporal_type)
      ->condition('entity_id', $entity_id);

    return $query;
  }

  /**
   * Defines a query of ALL data entries for a specfied temporal type and list of entity id's
   * @param string $temporal_type
   * @param array $entity_id_list
   *
   * @return \Drupal\Core\Database\Query\Select
   */
  public function prepareFieldValuesByTemporalTypeEntityIdList($temporal_type, $entity_id_list) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->startQuery();
    $this->injectDeltaStatusConditions($query);

    $query->condition('type', $temporal_type)
      ->condition('entity_id', $entity_id_list, 'IN');

    return $query;
  }

  /**
   * Defines a query of ALL data entries for a specfied temporal type list
   *
   * @param array $temporal_type_list
   *
   * @return \Drupal\Core\Database\Query\Select
   */
  public function prepareFieldValuesByTemporalTypeList($temporal_type_list) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->startQuery();
    $this->injectDeltaStatusConditions($query);

    $query->condition('type', $temporal_type_list, 'IN');

    return $query;
  }

  /**
   * Defines a query of ALL data entries for a specfied temporal type
   * 
   * @param string $temporal_type
   *
   * @return \Drupal\Core\Database\Query\Select
   */
  public function prepareFieldValuesByTemporalType($temporal_type) {
    return $this->prepareFieldValuesByTemporalTypeList([$temporal_type]);
  }

  /**
   * Defines a query of ALL data entries for an Entity Type and list of entity id's
   * @param string $entity_type
   * @param array $entity_id_list
   *
   * @return \Drupal\Core\Database\Query\Select
   */
  public function prepareFieldValuesByEntityTypeEntityIdList($entity_type, $entity_id_list) {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this->startQuery();
    $this->injectDeltaStatusConditions($query);

    $query->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id_list, 'IN');

    return $query;
  }
}
