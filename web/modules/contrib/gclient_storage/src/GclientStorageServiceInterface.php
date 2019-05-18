<?php

namespace Drupal\gclient_storage;

/**
 * Gclient Storage service interface.
 */
interface GclientStorageServiceInterface {

  /**
   * Convert file metadata returned from Google Storage into a metadata cache array.
   *
   * @param string $uri
   *   The uri of the resource.
   * @param mixed $object_metadata
   *   An array containing the collective metadata for the object in storage.
   *   The caller may send an empty array here to indicate that the returned
   *   metadata should represent a directory.
   *
   * @return array
   *   A file metadata cache array.
   */
  function convertMetadata($uri, $object_metadata);

}
