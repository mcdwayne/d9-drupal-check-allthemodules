<?php

namespace Drupal\civicrm_tools;

/**
 * Interface CiviCrmApiInterface.
 *
 * @todo fix documentation / signature with 'entity type' instead of 'entity'
 * entity is misleading, it denotes an occurrence, which is wrong.
 */
interface CiviCrmApiInterface {

  /**
   * Get an entity from CiviCRM, with optional filtering.
   *
   * Pagination defaults to 25.
   *
   * @param string $entity_id
   *   CiviCRM entity id.
   * @param array $params
   *   Optional parameters.
   *
   * @return array
   *   List of values.
   */
  public function get($entity_id, array $params);

  /**
   * Counts results for an entity in CiviCRM, with optional filtering.
   *
   * @param string $entity_id
   *   CiviCRM entity id.
   * @param array $params
   *   Optional parameters.
   *
   * @return int
   *   Amount of elements.
   */
  public function count($entity_id, array $params);

  /**
   * Get all entities, with optional filtering.
   *
   * @param string $entity_id
   *   CiviCRM entity id.
   * @param array $params
   *   Optional parameters.
   *
   * @return array
   *   List of values.
   */
  public function getAll($entity_id, array $params);

  /**
   * Delete an entity in CiviCRM.
   *
   * @param string $entity_id
   *   CiviCRM entity id.
   * @param array $params
   *   Parameters.
   *
   * @return array
   *   List of deleted values.
   */
  public function delete($entity_id, array $params);

  /**
   * Create an entity in CiviCRM.
   *
   * @param string $entity_id
   *   CiviCRM entity id.
   * @param array $params
   *   Parameters.
   *
   * @return array
   *   Array containing the status.
   */
  public function create($entity_id, array $params);

  /**
   * Save and update an entity in CiviCRM.
   *
   * Alias of create.
   *
   * @param string $entity_id
   *   CiviCRM entity id.
   * @param array $params
   *   Parameters.
   *
   * @return array
   *   Array containing the status.
   */
  public function save($entity_id, array $params);

  /**
   * Get fields from the CiviCRM entity.
   *
   * @param string $entity_id
   *   CiviCRM entity id.
   * @param string $action
   *   Action.
   *
   * @return array
   *   List of values.
   */
  public function getFields($entity_id, $action = 'create');

  /**
   * Get options for the CiviCRM entity field.
   *
   * @param string $entity_id
   *   CiviCRM entity id.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   List of values.
   */
  public function getOptions($entity_id, $field_name);

}
