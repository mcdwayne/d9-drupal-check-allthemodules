<?php

namespace Drupal\real_estate_rets;

/**
 * A query object.
 */
interface RetsQueryInterface {

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
   * Gets the query's resource.
   *
   * @return string
   *   The query's resource.
   */
  public function resource();

  /**
   * Gets the query's class.
   *
   * @return string
   *   The query's class.
   */
  public function class();

  /**
   * Gets the query's value.
   *
   * @return string
   *   The query's value.
   */
  public function query();

  /**
   * Gets the query's dmql.
   *
   * @return string
   *   The query's dmql.
   */
  public function dmql();

  /**
   * Gets the query's format.
   *
   * @return string
   *   The query's format.
   */
  public function format();

  /**
   * Gets the query's limit.
   *
   * @return string
   *   The query's limit.
   */
  public function limit();

  /**
   * Gets the query's standardnames.
   *
   * @return string
   *   The query's standardnames.
   */
  public function standardnames();

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
