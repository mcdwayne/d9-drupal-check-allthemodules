<?php

namespace Drupal\real_estate_openimmo;

/**
 * A query object.
 */
interface OpenImmoQueryInterface {

  /**
   * Gets the query's ID.
   *
   * @return string
   *   The query's ID.
   */
  public function id();

  /**
   * Gets the query's label.
   *
   * @return string
   *   The query's label.
   */
  public function label();

  /**
   * Gets the query's weight.
   *
   * @return string
   *   The query's weight.
   */
  public function weight();

  /**
   * Gets the query's key_field.
   *
   * @return string
   *   The query's key_field.
   */
  public function keyField();

  /**
   * Gets the query's entity.
   *
   * @return string
   *   The query's entity.
   */
  public function entity();

  /**
   * Gets the query's select.
   *
   * @return string
   *   The query's select.
   */
  public function select();

}
