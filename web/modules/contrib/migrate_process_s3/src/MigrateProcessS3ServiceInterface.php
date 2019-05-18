<?php

namespace Drupal\migrate_process_s3;

use Aws\S3\S3Client;

/**
 * Provides a method to download an object from S3 as an unmanaged file.
 */
interface MigrateProcessS3ServiceInterface {

  /**
   * Download an object from S3 and save it as an unmanaged file
   *
   * @param \Aws\S3\S3Client $s3
   *   The Amazon S3 client.
   * @param $bucket
   *   The bucket name.
   * @param $path
   *   The object path to download.
   * @param $dest_dir
   *   The destination directory within the public file directory.
   *
   * @return string|FALSE
   *   The URI to the file on success, FALSE otherwise.
   */
  public function downloadFile(S3Client $s3, $bucket, $path, $dest_dir);
}
