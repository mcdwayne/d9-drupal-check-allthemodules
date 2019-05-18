<?php

namespace Drupal\prepared_data\Shorthand;

/**
 * Interface for shorthand instances of prepared data keys.
 *
 * A shorthand is a kind of a mask, it doesn't directly expose
 * the data key to the public. The main purpose though is that
 * the shorthand ID can be used instead of the combination of
 * a data key plus subset keys, which can lead to a very large
 * Http query. The shorthand ID is short and maps internally
 * to a combination of data key plus subset keys.
 */
interface ShorthandInterface {

  /**
   * Get the shorthand ID.
   *
   * @return string
   *   The shorthand ID.
   */
  public function id();

  /**
   * Get the represented data key.
   *
   * @return string
   */
  public function key();

  /**
   * Get the subset keys.
   *
   * @return string|array
   *   The subset keys, if any.
   */
  public function subsetKeys();

  /**
   * Get the data key and subset keys as encoded Http query.
   *
   * @return string
   *   The data query.
   */
  public function getDataQuery();

}
