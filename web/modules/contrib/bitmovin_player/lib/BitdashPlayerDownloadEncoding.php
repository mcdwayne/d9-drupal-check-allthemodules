<?php

/**
 * @file
 * Download bitdash encoding.
 */

define('HMBKP_ZIP_PATH', 'PclZip');
use bitcodin\Bitcodin;
use bitcodin\ApiResource;

/**
 * Download of encoding to file system.
 */
class BitdashPlayerDownloadEncoding extends ApiResource {

  const URL_DOWNLOAD = '/job/{id}/download';
  const URL_DOWNLOAD_REQUEST = '/job/download';
  private $path;
  private $job;
  private $fileDirectory;

  /**
   * Initialize DownloadEncoding object.
   */
  public static function create($job, $path) {
    return new self($job, $path);
  }

  /**
   * Contructor method for DownloadEncoding.
   */
  public function __construct($job, $path) {
    $this->path = $path;
    $this->job = $job;
    $this->setApiToken();

    if (self::requestVideoDownload($this->job->jobId)) {
      $url = self::getDownloadUrl($this->job->jobId);
      $this->fileDirectory = self::downloadVideo($url, $this->path, $this->job->jobId);
    }
  }

  /**
   * Set the API token for Bitcodin.
   */
  protected function setApiToken() {
    // @FIXME
    // Could not extract the default value because it is either indeterminate,
    // or not scalar. You'll need to provide a default value in
    // config/install/bitdash_player.settings.yml and
    // config/schema/bitdash_player.schema.yml.
    Bitcodin::setApiToken(\Drupal::config('bitdash_player.settings')->get('bitdash_player_api_key'));
  }

  /**
   * Get download feed.
   */
  public static function requestVideoDownload($id) {
    $body = [
      'jobId' => $id,
    ];

    $response = self::_postRequest(self::URL_DOWNLOAD_REQUEST, json_encode($body), 200);
    $content = json_decode($response->getBody()->getContents());

    return !empty($content->jobId) ? $content->jobId : FALSE;
  }

  /**
   * Method to get the download url of the encoded vidoes.
   */
  public static function getDownloadUrl($id) {
    do {
      $download_status = self::getStatus($id);
      sleep(2);
    } while ($download_status->progress != 100);

    return $download_status->url;
  }

  /**
   * Get the status of the encoded video download prepare.
   */
  public static function getStatus($id) {
    $response = self::_getRequest(str_replace('{id}', $id, self::URL_DOWNLOAD), 200);
    return json_decode($response->getBody()->getContents());
  }

  /**
   * Download the video.
   */
  public static function downloadVideo($source, $destination, $folder_name) {
    if (file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      $extract_path = $destination . '/' . $folder_name;
      $zip_file = $destination . '/' . $folder_name . '.zip';

      file_put_contents($zip_file, fopen($source, 'r'));
      \Drupal::service("file_system")->chmod($zip_file);
      $realpath = \Drupal::service("stream_wrapper_manager")->getViaUri($zip_file)->realpath();

      $zip = new ZipArchive();
      if ($zip->open($realpath) !== TRUE) {
        $message = 'Zip opening is failed for source %source and zip file %file';
        $args = [
          '%source' => $source,
          '%file' => $zip_file,
        ];
        \Drupal::logger('bitdash_player')->error($message, []);

        return;
      }

      // Extract zip folder.
      if ($zip->extractTo($extract_path) !== TRUE) {
        $message = 'Zip extraction is failed for source %source and zip file %file';
        $args = [
          '%source' => $source,
          '%file' => $zip_file,
        ];
        \Drupal::logger('bitdash_player')->error($message, []);

        return;
      }
      $folder_name = $zip->getNameIndex(0);

      $zip->close();

      \Drupal::service("file_system")->chmod($extract_path);
      file_unmanaged_delete($zip_file);

      $zip_filename = $extract_path . '/' . $folder_name . $folder_name;
      self::copyDirectory($zip_filename, $extract_path);

      file_unmanaged_delete_recursive($extract_path . '/' . $folder_name);

      return $extract_path;
    }
  }

  /**
   * Copy directory method.
   */
  public static function copyDirectory($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (FALSE !== ($file = readdir($dir))) {
      if (($file != '.') && ($file != '..')) {
        if (is_dir($src . '/' . $file)) {
          self::copyDirectory($src . '/' . $file, $dst . '/' . $file);
        }
        else {
          copy($src . '/' . $file, $dst . '/' . $file);
        }
      }
    }
    closedir($dir);
  }

  /**
   * Public function to get file directory.
   */
  public function getFileDirectory() {
    return $this->fileDirectory;
  }

}
