<?php

namespace Drupal\past;


/**
 * Represents an event argument.
 */
interface PastEventArgumentInterface {

  /**
   * Returns the argument key.
   *
   * @return string
   *   The key of this argument.
   */
  public function getKey();

  /**
   * Returns the data of the event argument.
   *
   * @return mixed
   *   Based on the type, this returns either an array, if the data was an array
   *   or an object, a stdClass if it was an object or a scalar value.
   *
   * @todo. Standardize the return value, allow access to the metadata.
   */
  public function getData();

  /**
   * Returns the type of the argument.
   *
   * The argument type is automatically detected on save by ensureType().
   * It is either a native php type or the class name if data was an object.
   *
   * @return string
   *   The type of the argument data.
   */
  public function getType();

  /**
   * Returns the raw data assigned with the argument.
   *
   * @return mixed
   *   The raw data as passed to PastEventArgumentInterface::setRaw().
   */
  public function getRaw();

  /**
   * Add raw data to the argument, which will be saved as a serialized string.
   *
   * Use this if it is required to have access to the original data structure
   * later on.
   *
   * @param mixed $raw
   *   Any serializable data structure.
   */
  public function setRaw($raw);

  /**
   * Provides the original data.
   *
   * @return mixed
   *   The original data.
   */
  public function getOriginalData();

  /**
   * Sets the argument type according to the type of the original data.
   */
  public function ensureType();
}
