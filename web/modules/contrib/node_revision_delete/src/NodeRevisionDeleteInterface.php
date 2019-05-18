<?php

namespace Drupal\node_revision_delete;

/**
 * Interface NodeRevisionDeleteInterface.
 *
 * @package Drupal\node_revision_delete
 */
interface NodeRevisionDeleteInterface {

  /**
   * Update the max_number for a config name.
   *
   * We need to update the max_number in the existing content type configuration
   * if the new value (max_number) is lower than the actual, in this case the
   * new value will be the value for the content type.
   *
   * @param string $config_name
   *   Config name to update (when_to_delete or minimum_age_to_delete).
   * @param int $max_number
   *   The maximum number for $config_name parameter.
   *
   * @return array
   *   Return the node_revision_delete_track variable values.
   */
  public function updateTimeMaxNumberConfig($config_name, $max_number);

  /**
   * Return the time string for the config_name parameter.
   *
   * @param string $config_name
   *   The config name (minimum_age_to_delete|when_to_delete).
   * @param int $number
   *   The number for the $config_name parameter configuration.
   *
   * @return string
   *   The time string for the $config_name parameter.
   */
  public function getTimeString($config_name, $number);

  /**
   * Save the content type config variable.
   *
   * @param string $content_type
   *   Content type machine name.
   * @param int $minimum_revisions_to_keep
   *   Minimum number of revisions to keep.
   * @param int $minimum_age_to_delete
   *   Minimum age in months of revision to delete.
   * @param int $when_to_delete
   *   Number of inactivity months to wait for delete a revision.
   */
  public function saveContentTypeConfig($content_type, $minimum_revisions_to_keep, $minimum_age_to_delete, $when_to_delete);

  /**
   * Delete the content type config variable.
   *
   * @param string $content_type
   *   Content type machine name.
   *
   * @return bool
   *   Return TRUE if the content type config was deleted or FALSE if not
   *   exists.
   */
  public function deleteContentTypeConfig($content_type);

  /**
   * Return the available values for time frequency.
   *
   * @param string $index
   *   The index to retrieve.
   *
   * @return string
   *   The index value (human readable value).
   */
  public function getTimeValues($index = NULL);

  /**
   * Return the time option in singular or plural.
   *
   * @param string $number
   *   The number.
   * @param string $time
   *   The time option (days, weeks or months).
   *
   * @return string
   *   The singular or plural value for the time.
   */
  public function getTimeNumberString($number, $time);

  /**
   * Return the list of candidate nodes for node revision delete.
   *
   * @param string $content_type
   *   Content type machine name.
   * @param int $minimum_revisions_to_keep
   *   Minimum number of revisions to keep.
   * @param int $minimum_age_to_delete
   *   Minimum age in months of revision to delete.
   * @param int $when_to_delete
   *   Number of inactivity months to wait for delete a revision.
   *
   * @return array
   *   Array of nids.
   */
  public function getCandidatesNodes($content_type, $minimum_revisions_to_keep, $minimum_age_to_delete, $when_to_delete);

}
