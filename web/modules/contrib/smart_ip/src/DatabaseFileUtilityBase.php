<?php

/**
 * @file
 * Contains \Drupal\smart_ip\DatabaseFileUtilityBase.
 */

namespace Drupal\smart_ip;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Archiver\Zip;
use Drupal\Core\Archiver\Tar;


/**
 * Database file utility methods class wrapper.
 *
 * @package Drupal\smart_ip
 */
abstract class DatabaseFileUtilityBase implements DatabaseFileUtilityInterface {

  /**
   * Download Smart IP's data source module's database timout.
   */
  const DOWNLOAD_TIMEOUT = 600;

  /**
   * Fixed Drupal folder path of Smart IP data source module's database file is
   * stored.
   */
  const DRUPAL_FOLDER = 'private://smart_ip';

  /**
   * Drupal temporary folder path.
   */
  const DRUPAL_TEMP_FOLDER = 'temporary://smart_ip';

  /**
   * Download Smart IP's data source module's database file weekly.
   */
  const DOWNLOAD_WEEKLY = 0;

  /**
   * Download Smart IP's data source module's database file monthly.
   */
  const DOWNLOAD_MONTHLY = 1;

  /**
   * Get Smart IP's data source module's database file's path. This should
   * return the fixed Drupal folder if auto update is on or if custom path is
   * empty with auto update off.
   *
   * @param bool $autoUpdate
   * @param string $customPath
   * @return string
   */
  public static function getPath($autoUpdate, $customPath) {
    if ($autoUpdate == TRUE || ($autoUpdate == FALSE && empty($customPath))) {
      /** @var \Drupal\Core\File\FileSystem $filesystem */
      $filesystem = \Drupal::service('file_system');
      return $filesystem->realpath(self::DRUPAL_FOLDER);
    }
    return $customPath;
  }

  /**
   * {@inheritdoc}
   */
  public static function needsUpdate($lastUpdateTime, $autoUpdate = TRUE, $frequency = self::DOWNLOAD_MONTHLY) {
    if ($autoUpdate) {
      $timeNow = strtotime('midnight', \Drupal::time()->getRequestTime());
      $lastUpdateTime = strtotime('midnight', $lastUpdateTime);
      if ($frequency == self::DOWNLOAD_WEEKLY) {
        $wednesday = strtotime('first Wednesday', $timeNow);
        if ($wednesday <= $timeNow && $wednesday > $lastUpdateTime) {
          return TRUE;
        }
      }
      elseif ($frequency == self::DOWNLOAD_MONTHLY) {
        $firstWed = strtotime('first Wednesday of this month', $timeNow);
        if ($firstWed <= $timeNow && $firstWed > $lastUpdateTime) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Download Smart IP's data source module's database file and extract it.
   *
   * @param string $url
   *   URL of the Smart IP's data source module's service provider.
   * @param string $file
   *   File name of the Smart IP's data source module's database including its
   *   extension name.
   * @param string $sourceId
   *   Smart IP data source module's source ID.
   * @return bool
   *   Returns FALSE if process failed.
   */
  protected static function requestDatabaseFile($url, $file, $sourceId) {
    $destination = self::DRUPAL_FOLDER;
    $source      = self::DRUPAL_TEMP_FOLDER;
    /** @var \Drupal\Core\File\FileSystem $filesystem */
    $filesystem          = \Drupal::service('file_system');
    $sourceRealPath      = $filesystem->realpath($source);
    $destinationRealPath = $filesystem->realpath($destination);
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManager $streamWrapper */
    $streamWrapper = \Drupal::service('stream_wrapper_manager');
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $destinationStream */
    $destinationStream = $streamWrapper->getViaUri($destination);
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $sourceStream */
    $sourceStream = $streamWrapper->getViaUri($source);
    if (!file_exists($destination)) {
      // The Smart IP folder does not exist then create it.
      $destinationStream->mkdir($destination, NULL, STREAM_MKDIR_RECURSIVE);
      file_prepare_directory($destinationRealPath);
    }
    if (file_exists($destination)) {
      if (file_exists($source)) {
        // Remove old temporary database download directory if still exist.
        file_unmanaged_delete_recursive($source);
      }
      // Prepare temporary download directory.
      $sourceStream->mkdir($source, NULL, STREAM_MKDIR_RECURSIVE);
      // Download the Smart IP's data source module's database file.
      /** @var \Drupal\Core\Http\ClientFactory $client */
      $client = \Drupal::service('http_client_factory');
      /** @var \Psr\Http\Message\ResponseInterface $uriData */
      $uriData = $client->fromOptions(['timeout' => self::DOWNLOAD_TIMEOUT])
        ->get($url);
      /** @var \Psr\Http\Message\StreamInterface $data */
      $data = $uriData->getBody();
      $parsedUrl        = parse_url($url);
      $archivePath      = "$source/" . $filesystem->basename($parsedUrl['path']);
      $savedArchivePath = file_unmanaged_save_data($data, $archivePath, FILE_EXISTS_REPLACE);
      if (!$savedArchivePath) {
        $message = t('Failed to download %source.', ['%source' => $url]);
        \Drupal::state()->set('smart_ip.request_db_error_source_id', $sourceId);
        \Drupal::state()->set('smart_ip.request_db_error_message', $message);
        \Drupal::logger('smart_ip')->error($message);
        return FALSE;
      }
      $savedArchiveRealPath  = $filesystem->realpath($savedArchivePath);
      $sourceFilePath        = "$source/$file";
      $sourceFileRealPath    = "$sourceRealPath/$file";
      if (file_exists($sourceFilePath)) {
        // Remove old database file from temp extract directory if still exist.
        $sourceStream->unlink($sourceFilePath);
      }
      // Extract it.
      try {
        // Previous function used: update_manager_archive_extract().
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($savedArchiveRealPath) == 'application/zip') {
          $archive = new Zip($savedArchiveRealPath);
        }
        else {
          $archive = new Tar($savedArchiveRealPath);
        }
        $archive->extract($sourceRealPath);
      }
      catch (\Exception $e) {
        $extractError = TRUE;
        \Drupal::logger('smart_ip')->debug($e->getMessage());
        if (class_exists('PharData')) {
          try {
            $extractError = FALSE;
            $archive = new \PharData($savedArchiveRealPath);
            $archive->extractTo($sourceRealPath);
          }
          catch (\Exception $e) {
            \Drupal::logger('smart_ip')->debug($e->getMessage());
            if (!file_exists($sourceFilePath)) {
              $extractError = TRUE;
            }
          }
        }
        if ($extractError) {
          $sourceFp = gzopen($savedArchiveRealPath, 'rb');
          $targetFp = fopen($sourceFileRealPath, 'w');
          while (!gzeof($sourceFp)) {
            $data = gzread($sourceFp, 4096);
            fwrite($targetFp, $data, strlen($data));
          }
          gzclose($sourceFp);
          fclose($targetFp);
        }
      }
      // Verify it.
      if (!file_exists($sourceFilePath)) {
        $message = t('Failed extracting %file.', ['%file' => $savedArchiveRealPath]);
        \Drupal::state()->set('smart_ip.request_db_error_source_id', $sourceId);
        \Drupal::state()->set('smart_ip.request_db_error_message', $message);
        \Drupal::logger('smart_ip')->error($message);
        return FALSE;
      }
      $destinationFilePath     = "$destination/$file";
      $destinationFileRealPath = $filesystem->realpath($destinationFilePath);
      if (file_exists($destinationFilePath)) {
        // Delete the old Smart IP data source module's database file.
        $destinationStream->unlink($destinationFilePath);
      }
      if (file_unmanaged_move($sourceFilePath, $destinationFilePath) === FALSE) {
        $message = t('The file %file could not be moved to %destination.', [
          '%file' => $sourceFileRealPath,
          '%destination' => $destinationFileRealPath,
        ]);
        \Drupal::state()->set('smart_ip.request_db_error_source_id', $sourceId);
        \Drupal::state()->set('smart_ip.request_db_error_message', $message);
        \Drupal::logger('smart_ip')->error($message);
        return FALSE;
      }
      else {
        // Delete the temporary download directory.
        file_unmanaged_delete_recursive($source);
        // Success! Clear error flag and message.
        \Drupal::state()->set('smart_ip.request_db_error_source_id', '');
        \Drupal::state()->set('smart_ip.request_db_error_message', '');
        \Drupal::logger('smart_ip')->info(t('The database file %file successfully downloaded to %destination', [
            '%file' => $file,
            '%destination' => $destinationFileRealPath,
          ])
        );
      }
    }
    else {
      $message = t('Your private file system path is not yet configured. Please check your @filesystem.', [
        '@filesystem' => Link::fromTextAndUrl(t('File system'), Url::fromRoute('system.file_system_settings'))->toString(),
      ]);
      \Drupal::state()->set('smart_ip.request_db_error_source_id', $sourceId);
      \Drupal::state()->set('smart_ip.request_db_error_message', $message);
      return FALSE;
    }
    return TRUE;
  }

}
