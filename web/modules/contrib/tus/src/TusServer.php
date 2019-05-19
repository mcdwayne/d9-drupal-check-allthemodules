<?php

namespace Drupal\tus;

use TusPhp\Tus\Server as TusPhp;
use Drupal\file\Entity\File;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class TusServer.
 */
class TusServer implements TusServerInterface {

  /**
   * Constructs a new TusServer object.
   */
  public function __construct() {

  }

  /**
   * Determine the Drupal URI for a file based on TUS upload key and meta params
   * from the upload client.
   *
   * @param string $uploadKey
   *   The TUS upload key.
   * @param array $fieldInfo
   *   Params about the entity type, bundle, and field_name.
   *
   * @return string
   *   The intended destination uri for the file.
   */
  public function determineDestination($uploadKey, $fieldInfo = []) {
    $destination = '';
    // If fieldInfo was not passed, we cannot determine file path.
    if (empty($fieldInfo)) {
      throw new HttpException(500, 'Destination file path unknown because field info not sent in client meta.');
    }

    // Determine TUS uploadDir.
    $bundleFields = \Drupal::getContainer()->get('entity_field.manager')
      ->getFieldDefinitions($fieldInfo['entityType'], $fieldInfo['entityBundle']);
    $fieldDefinition = $bundleFields[$fieldInfo['fieldName']];
    // Get the field's configured destination directory.
    $filePath = trim(\Drupal::service('token')->replace($fieldDefinition->getSetting('file_directory')), '/');
    $destination = $fieldDefinition->getSetting('uri_scheme') . '://';
    $destination .= $filePath;

    // Add the UploadKey to destination.
    $destination .= '/' . $uploadKey;

    // Ensure directory creation.
    if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      throw new HttpException(500, 'Destination file path:' . $destination . ' is not writable.');
    }

    return $destination;
  }

  /**
   * Configure and return TusServer instance.
   *
   * @param string $uploadKey
   *   UUID for the file being uploaded.
   *
   * @return TusServer
   */
  public function getServer($uploadKey = '', $postData = []) {
    // Ensure TUS cache directory exists.
    $tusCacheDir = 'private://tus';
    if (!file_prepare_directory($tusCacheDir, FILE_CREATE_DIRECTORY)) {
      throw new HttpException(500, 'TUS cache folder "private://tus" is not writable.');
    }
    // Set TUS config cache directory.
    \TusPhp\Config::set([
      'file' => [
        'dir' => drupal_realpath('private://tus') . '/',
        'name' => 'tus_php.cache',
      ]
    ]);

    // Initialize TUS server.
    $server = new TusPhp();
    $server->setApiPath('/tus/upload');

    // Set uploadKey if passed.
    if (!empty($uploadKey)) {
      $server->setUploadKey($uploadKey);
    }

    // These methods won't pass metadata about the file, and don't need
    // file directory, because they are reading from TUS cache, so we
    // can return the server now.
    $requestMethod = strtolower($server->getRequest()->method());
    $fastReturnMethods = [
      'get',
      'head',
      'patch',
    ];
    if (in_array($requestMethod, $fastReturnMethods)) {
      return $server;
    }

    // Get uploadKey for directory creation. On POST, it isn't passed, but
    // we need to add the UUID to file directory to ensure we don't
    // concatenate same-file uploads if client key is lost.
    if ($requestMethod == 'post') {
      $uploadKey = $server->getUploadKey();
    }

    // Get the file destination.
    $destination = $this->determineDestination($uploadKey, $postData);
    // Set the upload directory for TUS.
    $server->setUploadDir(drupal_realpath($destination));

    return $server;
  }

  /**
   * Create the file in Drupal and send response.
   *
   * @param array  $postData
   *   Array of file details from TUS client.
   *
   * @return array
   *   The created file details.
   */
  public function uploadComplete($postData = []) {
    // If no post data, we can't proceed.
    if (empty($postData['file'])) {
      throw new HttpException(500, 'TUS uploadComplete did not receive file info.');
    }
    $fileExists = $fileEntityExists = $addUsage = FALSE;
    $fileUsage = \Drupal::service('file.usage');

    // Get UploadKey from Uppy response.
    $uploadUrlArray = explode('/', $postData['response']['uploadURL']);
    $uploadKey = array_pop($uploadUrlArray);

    // Get file destination.
    $destination = $this->determineDestination($uploadKey, $postData['file']['meta']);
    $fileName = $postData['file']['name'];
    $fileUri = $destination . '/' .  $fileName;

    // Check if the file already exists.  Re-use the existing entity if so.
    // We can do this because even if the filenames are the same on 2 different
    // files, the checksum performed by TUS will cause a new uploadKey, and
    // therefor a new folder and file entity entry.
    if (file_exists(drupal_realpath($fileUri))) {
      $fileExists = TRUE;
    }

    // Check if we have a file_managed record for the file anywhere.
    $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
    $fileQuery = $fileStorage->getQuery();

    // If the current path for this file already exists, we have exact match.
    if ($fileExists) {
      $fileQuery->condition('uri', $fileUri);
    }
    else {
      // We can look for this TUS-uuid + filename for the existence of this
      // file, possibly uploaded for a different field.
      $fileQuery->condition('uri', "%{$uploadKey}/{$fileName}", 'LIKE');
      $addUsage = TRUE;
    }

    $fileCheck = $fileQuery->execute();
    // If we found the file in database.
    if (!empty($fileCheck)) {
      $file = $fileStorage->load(reset($fileCheck));
      // Mark that the file exists, so we can re-use the entity.
      $fileEntityExists = TRUE;
      // Check if this file URI truly exists in path.
      if (file_exists(drupal_realpath($file->getFileUri()))) {
        $fileExists = TRUE;
      }
    }

    // If the file didn't already exist, create the record now.
    if ($fileExists && !$fileEntityExists) {
      // Create the file entity.
      $file = File::create([
        'uid'      => \Drupal::currentUser()->id(),
        'filename' => $fileName,
        'uri'      => $fileUri,
        'filemime' => $postData['file']['type'],
        'filesize' => $postData['file']['size'],
      ]);
      $file->save();

      // Create file_usage entry so the file isn't deleted before
      // containing entity is saved.
      // These entries are deleted in tus_cron if another file_usage is detected.
      // e.g. once the file is assigned to an entity.
      $fileUsage->add($file, 'tus', 'file', $file->id());
    }
    elseif (empty($file)) {
      // Return error.
      throw new HttpException(406, 'There was an issue uploading this file.');
    }

    if ($addUsage) {
      // We need to record new usage, but we don't know the entity ID it is
      // assigned to, so mark the entity type as module, because 'tus' will be
      // removed in tus_cron. Leave 'file' as the object so that the link in
      // file admin list is valid.
      $fileUsage->add($file, $postData['file']['meta']['entityType'], 'file', $file->id());
    }

    // Return a useful result payload for front end clients.
    $result = [
      'fid' => $file->id(),
      'uuid' => $file->uuid(),
      'mimetype' => $file->getMimeType(),
      'filename' => $file->getFilename(),
      'path' => $file->url(),
    ];

    // Allow modules to alter the response payload.
    \Drupal::moduleHandler()->alter('tus_upload_complete', $result, $file);

    return $result;
  }

}
