<?php
/**
 * @file
 * Defines XHProf sample collection interface.
 */

namespace Drupal\xhprof_sample\XHProfSample;

interface RunInterface {
  /**
   * Load a sample run by a storage-appropriate identifier.
   *
   * @param mixed $identifier
   *   The unique run identifier.
   *
   * @return mixed
   *   Metadata for the run (including raw data), or false if
   *   the run cannot be loaded.
   */
  public static function load($identifier);

  /**
   * Collect an array of all sample runs as structured metadata.
   *
   * @return array
   *   Metadata for the run (including raw data).
   */
  public static function collectAll();

  /**
   * Collect an array of select sample runs as structured metadata.
   *
   * @param string $meta_type
   *   The metadata key to search on.
   * @param string $meta_value
   *   The value to match for the $meta_key
   *
   * @return array
   *   Metadata for the run (including raw data).
   */
  public static function collectWhere($meta_type, $meta_value);

  /**
   * Purge all samples from storage.
   *
   * @return int
   *   Number of samples purged
   */
  public static function purge();

  /**
   * Set sample data for this run.
   *
   * @param string $sample_data
   *   Serialized raw sample data from xhprof_sample_disable()
   */
  public function setData($sample_data);

  /**
   * Set metadata for this run.
   *
   * @param array $run_metadata
   *   Array of key/value pairs of run info
   */
  public function setMetadata($run_metadata);

  /**
   * Save the run.
   *
   * @return bool
   *   TRUE if saved, otherwise FALSE
   */
  public function save();
}
