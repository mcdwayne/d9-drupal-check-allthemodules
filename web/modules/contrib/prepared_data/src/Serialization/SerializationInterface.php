<?php

namespace Drupal\prepared_data\Serialization;

use Drupal\prepared_data\PreparedDataInterface;

/**
 * Interface for serializing prepared data.
 */
interface SerializationInterface {

  /**
   * Encodes the given data to a string.
   *
   * Note that the encoding only handles data values.
   * The returned string neither contains any generated
   * meta information, expiry nor any key identifier.
   *
   * @param \Drupal\prepared_data\PreparedDataInterface $prepared_data
   *   The prepared data object to encode.
   *
   * @return string|NULL
   *   The encoding result as string, if successful.
   */
  public function encode(PreparedDataInterface $prepared_data);

  /**
   * Decodes the given string into a prepared data object.
   *
   * Note that the decoding only handles data values.
   * The returned object neither has any meta information,
   * expiry nor any key identifier assigned.
   *
   * @param string $encoded_data
   *   The string to decode.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface|NULL
   *   The decoding result as prepared data object, if successful.
   */
  public function decode($encoded_data);

}
