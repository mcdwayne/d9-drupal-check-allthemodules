<?php

namespace Drupal\kong\Plugin;

/**
 * Defines an interface for Kong object plugins.
 */
interface KongObjectInterface {

  /**
   * Adds new kong object.
   *
   * @param array $data
   *   An array of data to be save.
   *
   * @return array
   *   The kong object.
   */
  public function add(array $data);

  /**
   * Gets an kong object.
   *
   * @param string $id
   *   The id of the kong object.
   *
   * @return array|null
   *   The kong object.
   */
  public function get($id);

  /**
   * Queries kong objects.
   *
   * @param array $parameters
   *   An array of kong object properties to filter the list.
   * @param bool $count
   *   Returns total objects if TRUE, return list of objects otherwise.
   *
   * @return array|int
   *   An array of kong object.
   */
  public function query(array $parameters = [], bool $count = FALSE);

  /**
   * Updates an existing kong object.
   *
   * @param string $id
   *   The id of the kong object.
   * @param array $data
   *   An array of data to be save.
   *
   * @return array
   *   The kong object.
   *
   * @throws \GuzzleHttp\Exception\ClientException
   */
  public function update($id, array $data);

  /**
   * Deletes an kong object.
   *
   * @param string $id
   *   The id of the kong object.
   */
  public function delete($id);

}
