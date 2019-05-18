<?php

namespace Drupal\permutive\Plugin;

/**
 * Interface PermutiveDataInterface.
 *
 * @package Drupal\permutive\Plugin
 */
interface PermutiveDataInterface {

  /**
   * The the client type.
   *
   * @return string
   *   The client type.
   */
  public function getClientType();

  /**
   * Sets the client type.
   *
   * @param string $client_type
   *   The type to set, for example 'web'.
   *
   * @return $this
   *   The data object.
   */
  public function setClientType($client_type);

  /**
   * Gets data from this data object.
   *
   * @param string $key
   *   A string that maps to a key within the configuration data.
   *
   * @code
   *   array(
   *     'foo' => array(
   *       'bar' => 'baz',
   *     ),
   *   );
   * @endcode
   *   A key of 'foo.bar' would return the string 'baz'. However, a key of 'foo'
   *   would return array('bar' => 'baz').
   *   If no key is specified, then the entire data array is returned.
   *
   * @return mixed
   *   The data that was requested.
   */
  public function get($key = '');

  /**
   * Gets the data array.
   *
   * @return array
   *   The data array.
   */
  public function getArray();

  /**
   * Replaces the data of this data object.
   *
   * @param array $data
   *   The new data.
   *
   * @return $this
   *   The data object.
   */
  public function setData(array $data);

  /**
   * Sets a value in this data object.
   *
   * @param string $key
   *   Identifier to store value in.
   * @param mixed $value
   *   Value to associate with identifier.
   *
   * @return $this
   *   The data object.
   *
   * @throws \Drupal\permutive\Plugin\PermutiveDataException
   *   If $value is an array and any of its keys in any depth contains a dot.
   */
  public function set($key, $value);

  /**
   * Unsets a value in this data object.
   *
   * @param string $key
   *   Name of the key whose value should be unset.
   *
   * @return $this
   *   The data object.
   */
  public function clear($key);

  /**
   * Merges data into a data object.
   *
   * @param array $data_to_merge
   *   An array containing data to merge.
   *
   * @return $this
   *   The data object.
   */
  public function merge(array $data_to_merge);

}
