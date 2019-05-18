<?php

/**
 * @file
 * Helper to work with files.
 */

namespace Drupal\droogle;

/**
 * Class DroogleFiles
 * @package Drupal\droogle
 * Contains some functions to work with files.
 */
class DroogleFiles {

  /**
   * @param \Google_Service_Drive $service
   *  Instance of Google_Service_Drive. Can be created in class DroogleConnector.
   * @param $params
   *  Parameters for search. @see https://developers.google.com/drive/v3/web/search-parameters
   *   @see Google/Service/Drive.php::listFiles() @see template_preprocess_droogle_list_files()
   *
   * @return mixed
   *    List of the files.
   */
  public static function searchForFile(\Google_Service_Drive $service, $params) {
    $files = $service->files->listFiles($params);
    $file_list = $files->getItems();
    return $file_list;
  }

  /**
   * @param \Google_Service_Drive $service
   *  Instance of Google_Service_Drive. Can be created in class DroogleConnector.
   * @param \Google_Service_Drive_DriveFile $file
   *  Object of the file, which can be received in method searchForFile().
   * @param $destination_dir
   *  Destination directory for the copied file, without trailing slash.
   * @return null
   */
  public static function downloadFile(\Google_Service_Drive $service, \Google_Service_Drive_DriveFile $file, $destination_dir) {
    $downloadUrl = $file->getDownloadUrl();
    if ($downloadUrl) {
      $request = new \Google_Http_Request($downloadUrl, 'GET', null, null);
      $httpRequest = $service->getClient()->getAuth()->authenticatedRequest($request);
      if ($httpRequest->getResponseHttpCode() == 200) {
        $file_content = $httpRequest->getResponseBody();
        $file_name = $file->getTitle();
        return boolval(file_put_contents("$destination_dir/$file_name", $file_content));
      } else {
        // An error occurred.
        return FALSE;
      }
    } else {
      // The file doesn't have any content stored on Drive.
      return FALSE;
    }
  }
}
