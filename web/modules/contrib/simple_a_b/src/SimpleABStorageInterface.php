<?php

namespace Drupal\simple_a_b;

/**
 * Storage Interface.
 */
interface SimpleABStorageInterface {

  /**
   * Add a new test to the database.
   *
   * @param object $data
   *   Pass in array.
   *
   * @return int
   *   Returns the created id.
   */
  public function create($data);

  /**
   * Update an existing test in the database.
   *
   * @param int $tid
   *   Tid.
   * @param object $data
   *   Pass in array.
   *
   * @return int
   *   Returns tid.
   */
  public function update($tid, $data);

  /**
   * Remove a test from the database.
   *
   * @param int $tid
   *   Tid.
   *
   * @return mixed
   *   Returns removed state.
   */
  public function remove($tid);

  /**
   * Pull a test from the database.
   *
   * @param int $tid
   *   Tid.
   *
   * @return mixed
   *   Returns object.
   */
  public function fetch($tid);

}
