<?php

namespace Drupal\migrate_process_s3;
use Aws\S3\S3Client;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Provides a method to download an object from S3 as an unmanaged file.
 */
class MigrateProcessS3Service implements MigrateProcessS3ServiceInterface {

  /**
   * Drupal\Core\File\FileSystemInterface definition.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new MigrateProcessS3Service object.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function downloadFile(S3Client $s3, $bucket, $path, $dest_dir) {

    // Get the public file directory path.
    $public_dir_abs_path = $this->fileSystem->realpath('public://');

    // Generate the destination relative path.
    $rel_dest_path =
      trim($dest_dir, DIRECTORY_SEPARATOR) .
      DIRECTORY_SEPARATOR .
      $path;

    // Generate the destination absolute path for checking operations.
    $dest_abs_path = $public_dir_abs_path . DIRECTORY_SEPARATOR . $rel_dest_path;

    // Check if the file needs download, if not, return the file entity.
    if (!$this->needsDownload($s3, $bucket, $path, $dest_abs_path)) {
      return 'public://' . $rel_dest_path;
    }

    // Get the file from S3.
    $response = $s3->getObject([
      'Bucket' => $bucket,
      'Key' => $path,
    ]);

    // Create the directory if necessary.
    if(!$this->createDirectory($dest_abs_path)) {
      return FALSE;
    }

    // Finally, save the file.
    return file_unmanaged_save_data($response['Body'], 'public://' . $rel_dest_path, FILE_EXISTS_REPLACE);
  }

  /**
   * Determines if the file requires download or not.
   *
   * @param \Aws\S3\S3Client $s3
   *   The S3 client.
   * @param $bucket
   *   The S3 bucket name.
   * @param $path
   *   The object path.
   * @param $dest_abs_path
   *   The absolute path at which to save the file.
   *
   * @return bool
   *   TRUE when the file needs to be downloaded, FALSE otherwise.
   *
   * @throws \Exception
   */
  protected function needsDownload(S3Client $s3, $bucket, $path, $dest_abs_path) {
    // Check if the file exists.
    if (file_exists($dest_abs_path)) {
      // Get the file modification time.
      $mod_time = new \DateTime();
      $mod_time->setTimestamp(filemtime($dest_abs_path));

      // Check the object on S3, but don't download it.
      $head = $s3->headObject([
        'Bucket' => $bucket,
        'Key' => $path,
        'IfModifiedSince' => $mod_time,
      ]);

      // If we get a 304, there's no need to download the file.
      $result = $head->toArray();
      if (!empty($result['@metadata']['statusCode'])) {
        return !$result['@metadata']['statusCode'] == '304';
      }
    }

    // Otherwise, download.
    return TRUE;
  }

  /**
   * Creates a directory to save the file.
   *
   * @param $file_url
   *   The absolute file path at which to save the destination file.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  protected function createDirectory($file_url) {
    // Get the directory path.
    $dir_path = dirname($file_url);

    // Skip if the directory already exists.
    if (is_dir($dir_path)) {
      return TRUE;
    }

    // Otherwise create the directory at the specified path.
    return $this->fileSystem->mkdir($dir_path, NULL, TRUE);
  }

}
