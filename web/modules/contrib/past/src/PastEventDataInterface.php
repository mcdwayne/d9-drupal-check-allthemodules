<?php

namespace Drupal\past;

/**
 * Represents event data.
 */
interface PastEventDataInterface {

  /**
   * Returns the ID of the data, if saved.
   *
   * @return int
   *   The event ID.
   */
  public function id();

  /**
   * Returns the data key.
   *
   * @return string
   *   The key of this argument.
   */
  public function getKey();

  /**
   * Returns the type of the data.
   *
   * This is detected automatically on save and is either a native php type or
   * the name of the class if data was an object.
   *
   * @return string
   *   The type of the argument data.
   */
  public function getType();

}
