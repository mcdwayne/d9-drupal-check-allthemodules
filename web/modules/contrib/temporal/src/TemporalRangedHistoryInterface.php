<?php
/**
 * @file
 * Contains \Drupal\temporal\TemporalRangedHistoryInterface.
 */

namespace Drupal\temporal;

interface TemporalRangedHistoryInterface {

  /**
   * Get the entity field values used in the data for a particular temporal type.
   *
   * If, for instance, this is called for a temporal type that's tracking a taxonomy
   * term reference field, it will return an array of all term TIDs that are actually
   * used in the data set.
   *
   * @param string $temporal_type
   * @param callable $reducer
   * @return array
   */
  public function getUsedEntityFieldValues($temporal_type, callable $reducer);

  /**
   * Get filtered entity IDs.
   *
   * Returns an array of time period rows keyed by start timestamp, in which each
   * element is an array of entity IDs for which the given temporal type's value
   * returns true in a particular filter function.
   *
   * This can be used, for instance, to get a list of all the entity IDs that had
   * a particular tag value in each time period.
   *
   * Example usage:
   *
   * $entity_ids_by_period = $this->getFilteredEntityIDs(
   *   'article_tag',
   *   [$this, 'reduceToClose'], // for each period, use the last chronological value
   *   function($value) { return $value == 3; }
   * );
   *
   * Which might return:
   *
   * array(
   *   $timestamp1 => array(
   *     1, 4, 5, 7, 9
   *   ),
   *   $timestamp2 => array(
   *     1, 4, 5, 7, 9, 15, 20
   *   )
   * )
   *
   * @param string $temporal_type
   * @param callable $reducer
   * @param callable $filter
   * @return array
   */
  public function getFilteredEntityIDs($temporal_type, callable $reducer, callable $filter);

  /**
   * Get Grouped and Filtered Entity IDs.
   *
   * Similar to getFilteredEntityIDs(), except that the return array has an additional
   * grouping layer.  Suppose, for example, that you want to get a ranged list of articles
   * that use a certain tag, grouped by the value they have in a different taxonomy field
   * (for instance, "category").  You could do this as follows:
   *
   * $entity_ids_by_period = $this->getGroupedFilteredEntityIDs(
   *   'article_tag',
   *   [$this, 'reduceToClose'],
   *   function($value) { return $value == 3; },
   *   'article_category',
   *   [$this, 'reduceToClose']
   * );
   *
   * What you'd get in return would look something like this:
   *
   * array(
   *   $timestamp1 => array(
   *     $category_value_1 => array(
   *       1, 6, 9, 34
   *     ),
   *     $category_value_2 => array(
   *       2, 6, 10, 47
   *     ),
   *   ),
   *   $timestamp2 => array(
   *     // ...and so on...
   *   ),
   * )
   *
   * @param string $temporal_type
   * @param callable $reducer
   * @param callable $filter
   * @param string $grouping_temporal_type
   * @param callable $grouping_reducer
   * @return array
   */
  public function getGroupedFilteredEntityIDs($temporal_type, callable $reducer, callable $filter, $grouping_temporal_type, callable $grouping_reducer);

  /**
   * Get Grouped and Reduced Period Values By Entity ID.
   *
   * Takes the results of getGroupedFilteredEntityIDs() and applies an additional reduction
   * to the innermost array.  Primarily useful in reducing each of the sets to a count, like so:
   *
   * $counts_by_period = $this->getGroupedReducedPeriodValuesByEntityID(
   *   'article_tag',
   *   [$this, 'reduceToClose'],
   *   function($value) { return $value == 3; },
   *   'article_category',
   *   [$this, 'reduceToClose'],
   *   [$this, 'reduceToCount']
   * )
   *
   * Which would return something like:
   *
   * array(
   *   $timestamp1 => array(
   *     $category_value_1 => 10,
   *     $category_value_2 => 30,
   *     // ...etc...
   *   ),
   * )
   *
   * @param string $temporal_type
   * @param callable $reducer
   * @param callable $filter
   * @param string $grouping_temporal_type
   * @param callable $grouping_reducer
   * @param callable $period_reducer
   * @return array
   */
  public function getGroupedReducedPeriodValuesByEntityID($temporal_type, callable $reducer, callable $filter, $grouping_temporal_type, callable $grouping_reducer, callable $period_reducer);

  /**
   * Get Reduced Entity Field Values.
   *
   * Returns an array of time period rows keyed by start timestamp, in which each
   * element is an array showing a single field value for each entity ID temporal knows
   * about.  That value is determined by the $reducer callback, which is used to
   * collapse multiple values for the same entity into a single value that represents
   * the entire period.
   *
   * For instance, suppose you're tracking category assignments for article nodes,
   * and node 123 changes its category assignment twice in a month.  This function is
   * intended to return a single value for each entity per date period, so it has to
   * take those two changes and reduce them somehow into a single value representative
   * of the entire date period.  This is done by passing the $reducer callback into
   * PHP's array_reduce() function.
   *
   * Several useful reducer methods are provided for in the TemporalRangedHistory class:
   * - reduceToOpen() uses the first ("opening") change as the value
   * - reduceToClose() uses the last ("closing") change as the value
   * - and several others in the reduceTo* namespace
   *
   * Example usage:
   *
   * $categories_by_period = $this->getReducedEntityFieldValues(
   *   'article_category',
   *   [$this, 'reduceToClose']
   * );
   *
   * Which would return something like:
   *
   * array(
   *   $timestamp_1 => array(
   *     $entity_id_1 => 3,
   *     $entity_id_2 => 6,
   *     $entity_id_3 => 3,
   *     // ..etc..
   *   ),
   *   $timestamp_2 => array(
   *     // ..etc..
   *   ),
   * )
   *
   * @see getFilteredEntityIDs()
   *
   * @param $temporal_type
   * @param callable $reducer
   * @return array
   */
  public function getReducedEntityFieldValues($temporal_type, callable $reducer);

  /**
   * Get Reduced Period Values.
   *
   * Takes the results of getReducedEntityFieldValues() and reduces them further
   * into a single value per time period.
   *
   * @see getGroupedReducedPeriodValues()
   *
   * @param string $temporal_type
   * @param callable $reducer
   * @param callable $period_reducer
   * @return array
   */
  public function getReducedPeriodValues($temporal_type, callable $reducer, callable $period_reducer);

  /**
   * Get Grouped Reduced Entity Field Values.
   *
   * Similar to getReducedEntityFieldValues(), but provides an additional grouping layer.
   * For more information on how the grouping works, see getGroupedFilteredEntityIDs().
   *
   * @see getGroupedFilteredEntityIDs()
   *
   * @param string $temporal_type
   * @param callable $reducer
   * @param string $grouping_temporal_type
   * @param callable $grouping_reducer
   * @return array
   */
  public function getGroupedReducedEntityFieldValues($temporal_type, callable $reducer, $grouping_temporal_type, callable $grouping_reducer);

  /**
   * Get Grouped Reduced Period Values.
   *
   * Takes the results of getGroupedReducedEntityFieldValues() and reduces them further,
   * into a single value per period.
   *
   * @see getGroupedReducedPeriodValuesByEntityID()
   *
   * @param string $temporal_type
   * @param callable $reducer
   * @param string $grouping_temporal_type
   * @param callable $grouping_reducer
   * @param callable $period_reducer
   * @return array
   */
  public function getGroupedReducedPeriodValues($temporal_type, callable $reducer, $grouping_temporal_type, callable $grouping_reducer, callable $period_reducer);

}