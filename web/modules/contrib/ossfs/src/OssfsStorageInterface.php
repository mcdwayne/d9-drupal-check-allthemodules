<?php

namespace Drupal\ossfs;

/**
 * Defines an interface for file (directory or regular file) metadata storage.
 *
 * Classes implementing this interface allow reading and writing metadata from
 * and to the storage.
 */
interface OssfsStorageInterface {

  /**
   * Returns whether a file metadata exists.
   *
   * @param string $uri
   *   The uri of a file metadata to test.
   *
   * @return bool
   *   TRUE if the file metadata exists, FALSE otherwise.
   */
  public function exists($uri);

  /**
   * Reads file metadata from the storage.
   *
   * @param string $uri
   *   The uri of file metadata to load.
   *
   * @return array|bool
   *   The file metadata, or FALSE if not found.
   */
  public function read($uri);

  /**
   * Reads file metadata from the storage.
   *
   * @param array $uris
   *   List of uris of the file metadata to load.
   *
   * @return array
   *   A list of the file metadata, keyed by uri.
   */
  public function readMultiple(array $uris);

  /**
   * Writes file metadata to the storage.
   *
   * @param string $uri
   *   The uri of a file metadata to write.
   * @param array $data
   *   The metadata to write.
   *
   * @return bool
   *   TRUE on success, FALSE in case of an error.
   */
  public function write($uri, array $data);

  /**
   * Deletes file metadata from the storage.
   *
   * @param string $uri
   *   The uri of a file metadata to delete.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function delete($uri);

  /**
   * Renames uri of a file metadata in the storage.
   *
   * If $new_uri exists, this file will be overwritten. This behavior is
   * identical to the PHP rename() function.
   *
   * @param string $uri
   *   The uri of a file metadata to rename.
   * @param string $new_uri
   *   The new uri of a file metadata.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function rename($uri, $new_uri);

  /**
   * Gets file uris starting with a given prefix.
   *
   * Note: if the prefix is not empty and with a trailing slash, its children
   * NOT including grandchildren will be returned. If the prefix is empty, all
   * its children including grandchildren will be returned.
   *
   * Given the following uris:
   *
   * - oss://0/a.txt
   * - oss://0/1
   * - oss://0/1/a.txt
   *
   * Passing the prefix 'oss://0/' will return an array containing uris below:
   * - oss://0/a.txt
   * - oss://0/1
   *
   * Passing the prefix '' will return an array containing uris below:
   * - oss://0/a.txt
   * - oss://0/1
   * - oss://0/1/a.txt
   *
   * @param string $prefix
   *   The path prefix to search for, either a string with a trailing slash or
   *   an empty string.
   *
   * @return array
   *   An array containing matching uris.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the prefix is malformed.
   */
  public function listAll($prefix);

}
