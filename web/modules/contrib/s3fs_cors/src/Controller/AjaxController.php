<?php

namespace Drupal\s3fs_cors\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\file\Entity\File;
use Drupal\s3fs\StreamWrapper\S3fsStream;
use Aws\S3\S3Client;

/**
 * Default controller for the s3fs_cors module.
 */
class AjaxController extends ControllerBase {

  /**
   * Return the file key (i.e. the path and name).
   *
   * The values $file_size and $file_index are just values to be passed through
   * and returned to the javaScript function.
   */
  public function getKey($directory, $file_name, $file_size, $file_index, $replace = FILE_EXISTS_RENAME) {

    // Strip control characters (ASCII value < 32). Though these are allowed in
    // some filesystems, not many applications handle them well.
    $file_name = preg_replace('/[\x00-\x1F]/u', '_', $file_name);
    // Also replace forbidden chars if this is a Windows envrionment.
    if (substr(PHP_OS, 0, 3) == 'WIN') {
      // These characters are not allowed in Windows filenames.
      $file_name = str_replace([':', '*', '?', '"', '<', '>', '|'], '_', $file_name);
    }

    // Decode the "/" chars in the directory and build an initial file key.
    // Note: this will include the s3fs root folder, if specified.
    $directory = str_replace('::', '/', $directory);
    $file_key = $directory . '/' . $file_name;

    // Check if a file with this key already exists on S3.
    $file_exists = $this->s3FileExists($file_key);

    if ($file_exists) {
      switch ($replace) {
        case FILE_EXISTS_REPLACE:
          // Do nothing here, we want to overwrite the existing file.
          break;

        case FILE_EXISTS_RENAME:
          $file_key = $this->createFileKey($directory, $file_name);
          break;

        case FILE_EXISTS_ERROR:
          // Error reporting handled by calling function.
          return FALSE;
      }
    }
    // Core file_destination is not able to check remoe file existience.
    return new JsonResponse([
      'file_key' => $file_key,
      'file_name' => $file_name,
      'file_size' => $file_size,
      'file_index' => $file_index,
      ]);
  }

  /**
   * Create a new file key if the original one already exists.
   */
  private function createFileKey($directory, $file_name) {

    // Remove the root folder from the file directory if specified.
    $root_folder = '';
    $config = \Drupal::config('s3fs.settings');
    if (!empty($config->get('root_folder'))) {
      $root_folder = $config->get('root_folder') . '/';
      $directory = str_replace($root_folder, '', $directory);
    }

    $separator = '/';
    // A URI or path may already have a trailing slash or look like "public://".
    if (substr($directory, -1) == '/') {
      $separator = '';
    }

    // Extract the file base name and the file extension (with leading period).
    $base_name = substr($file_name, 0, strrpos($file_name, '.'));
    $extension = substr($file_name, strrpos($file_name, '.'));

    $key_base = $root_folder . $directory . $separator . $base_name;


    // Look in the s3fs cache to find files with a key like this.
    $uri_base = 's3://' . $directory . $separator . $base_name;
    $records = \Drupal::database()->select('s3fs_file', 's')
      ->fields('s', ['uri'])
      ->condition('uri', db_like($uri_base) . '%', 'LIKE')
      ->execute()
      ->fetchCol();

    // Process the results array to extract the suffix values
    $results = [];
    foreach ($records as $record) {
      $suffix = str_replace([$uri_base, $extension], '', $record);
      if ($suffix) {
        // Drop the leading underscore char.
        $suffix = (int) substr($suffix, 1);
        $results[$suffix] = $record;
      }
    }

    // Find a key suffix that can be used by looking for a gap in suffix values.
    for ($suffix = 0; $suffix < count($results); $suffix++) {
      if (!isset($results[$suffix])) {
        break;
      }
    }
    // If we drop out the bottom then suffix will be one greater then largest
    // existing value.  Create a trial key and test.
    $trial_key = $key_base . '_' . $suffix . $extension;

    if ($this->s3FileExists($trial_key)) {
      // Destination file already exists, then cache is stale. Rebuild required.
      \Drupal::logger('s3fs')->info('S3fs cache table rebuild required (key %key missing)',
        ['%key' => $trial_key]);
      // Look for a new suffix value greater then the largest already known.
      $suffix = max(array_keys($results));
      do {
        $trial_key = $key_base . '_' . ++$suffix . $extension;
      } while ($this->s3FileExists($trial_key));
    }

    return $trial_key;
  }

  /**
   * Check whehter a passed file name exists (using the file key).
   */
  private function s3FileExists($key) {
    $config = \Drupal::config('s3fs.settings');
    $client = new S3Client([
      'credentials' => [
        'key'    => $config->get('access_key') ?: Settings::get('s3fs.access_key', ''),
        'secret' => $config->get('secret_key') ?: Settings::get('s3fs.secret_key', ''),
      ],
      'region'  => $config->get('region'),
      'version' => '2006-03-01',
    ]);
    $bucket = $config->get('bucket');
    return $client->doesObjectExist($bucket, $key);
  }

  /**
   * Save the file details to the managed file table.
   */
  public function saveFile($file_path, $file_name, $file_size, $field_name) {
    $user = \Drupal::currentUser();

    // Decode the "/" chars from file path.
    $file_path = str_replace('::', '/', $file_path);

    // Remove the root folder from the file path if specified.
    $config = \Drupal::config('s3fs.settings');
    if (!empty($config->get('root_folder'))) {
      $root_folder = $config->get('root_folder');
      $file_path = str_replace($root_folder . '/', '', $file_path);
    }

    $file_uri = 's3://' . $file_path;

    // Record the uploaded file in the s3fs cache. This needs to be done before
    // the file is saved so the the filesize can be found from the cache.
    $wrapper = new S3fsStream();
    $wrapper->writeUriToCache($file_uri);

    $file_mime = \Drupal::service('file.mime_type.guesser')->guess($file_name);

    $values = [
      'uid' => $user->id(),
      'status' => 0,
      'filename' => $file_name,
      'uri' => $file_uri,
      'filesize' => $file_size,
      'filemime' => $file_mime,
    ];
    $file = File::create($values);
    $file->source = $field_name;
    $file->save();

    $values['fid'] = $file->id();
    $values['uuid'] = $file->uuid();

    return new JsonResponse($values);
  }

}
