<?php
/**
 * @file
 * Contains \Drupal\temporal\TemporalRangeService.
 */
namespace Drupal\temporal;

use Drupal\Core\Database\Connection;


class TemporalRangeService extends TemporalListService {
  /**
   * Constructor.
   * @param \Drupal\Core\Database\Connection $connection
   *  The database connection.
   */
  public function __construct(Connection $connection) {
    parent::__construct($connection);

  }

   /**
    * Retrieves records bounded by a specific Temporal type and an entity_id
   * @param integer $start_date
   * @param integer $end_date
   * @param string $temporal_type
   * @param integer $entity_id

   * @return array
   */
  public function rangeFieldValuesByTemporalTypeEntityId($start_date, $end_date, $temporal_type, $entity_id) {
    // It is necessary to try and pull in -1 and +1 records for a date range query
    // As the data is not always in regular periods, we need to ensure
    $query_array['start_query'] = parent::prepareFieldValuesByTemporalTypeEntityId($temporal_type, $entity_id);
    $query_array['between_query'] = parent::prepareFieldValuesByTemporalTypeEntityId($temporal_type, $entity_id);
    $query_array['end_query'] = parent::prepareFieldValuesByTemporalTypeEntityId($temporal_type, $entity_id);

    // Grab the entry immediately prior to the start date
    // This query may not return a result if the start date is before the first record in the between query
    // Thus the first record from the between query indicates the start date of the first instance
    $query_array['start_query']->condition('created', $start_date, '<')
      ->range(0, 1)
      ->orderBy('created', 'DESC');

    // Build the Between query
    $query_array['between_query']->condition('created', $start_date, '>=')
      ->condition('created', $end_date, '<=')
      ->orderBy('created');

    // Grab the entry immediately after to the end date
    // This query may not return a result if the end date is after the last record in the between query
    // Thus the last record from the between query indicates the last known instance date
    $query_array['end_query']->condition('created', $end_date, '>')
      ->range(0, 1)
      ->orderBy('created');

    return $query_array;
  }

  /**
   * Retrieves records bounded by date range for a specified Temporal Type
   * @param integer $start_date
   * @param integer $end_date
   * @param string $temporal_type
   *
   * @return array
   */
  public function rangeFieldValuesByTemporalType($start_date, $end_date, $temporal_type) {
    // Two extra queries are needed to pull in -1 and +1 records for a date range query
    // As the data is not always in regular intervals we need to ensure to capture a wider window
    // So that a historical representation can be described.
    $query_array['start_query'] = parent::prepareFieldValuesByTemporalType($temporal_type);
    $query_array['between_query'] = parent::prepareFieldValuesByTemporalType($temporal_type);
    $query_array['end_query'] = parent::prepareFieldValuesByTemporalType($temporal_type);

    // Grab the entry immediately prior to the start date
    // This query may not return a result if the start date is before the first record in the between query
    // Thus the first record from the between query indicates the start date of the first instance
    $query_array['start_query']->condition('created', $start_date, '<')
      ->range(0, 1)
      ->orderBy('created', 'DESC');

    // Build the Between query
    $query_array['between_query']->condition('created', $start_date, '>=')
      ->condition('created', $end_date, '<=')
      ->orderBy('created');

    // Grab the entry immediately after to the end date
    // This query may not return a result if the end date is after the last record in the between query
    // Thus the last record from the between query indicates the last known instance date
    $query_array['end_query']->condition('created', $end_date, '>')
      ->range(0, 1)
      ->orderBy('created');

    return $query_array;
  }

  /**
   * Allows for three range queries to pull in nearby records
   *
   * @param array $query_array
   * @return array
   *
   * UNION Example (Needs patch to core)
   * This is the working example of using two unions on the first query
   * Only works with a patch to /core/lib/Drupal/Core/Database/Query/Select.php
   * The patch implements placing () around each of the SELECT statements
   * Related issue: https://www.drupal.org/node/1145076
   * Locally created patch: docroot/modules/custom/temporal/experimental-select-union-patch.diff
   * This patch is not applied, but can be used for testing
   *
   *
   * $this->injectFields($query_array['start_query']);
   * $this->injectFields($query_array['between_query']);
   * $this->injectFields($query_array['end_query']);
   *
   * $query_array['start_query']->union($query_array['between_query'])
   * ->union($query_array['end_query']);
   *
   * $results = parent::getResults($query_array['start_query']);
   *
   */
  public function getResults($query_array) {
    $start_result = parent::getResults($query_array['start_query']);
    $between_results = parent::getResults($query_array['between_query']);
    $end_result = parent::getResults($query_array['end_query']);

    $results = array_merge($start_result, $between_results, $end_result);


    return $results;
  }
}