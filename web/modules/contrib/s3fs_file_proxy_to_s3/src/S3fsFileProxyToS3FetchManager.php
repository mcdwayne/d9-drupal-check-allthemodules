<?php

namespace Drupal\s3fs_file_proxy_to_s3;

use Drupal\Component\Utility\UrlHelper;
use Drupal\stage_file_proxy\FetchManager;
use GuzzleHttp\Exception\ClientException;

class S3fsFileProxyToS3FetchManager extends FetchManager {

  /**
   * {@inheritdoc}
   */
  public function fetch($server, $remote_file_dir, $relative_path, array $options) {
    try {
      // Fetch remote file.
      $url = $server . '/' . UrlHelper::encodePath($remote_file_dir . '/' . $relative_path);
      $response = $this->client->get($url, $options);

      if ($response->getStatusCode() == 200) {
        // Prepare local target directory and save downloaded file.
        $file_dir = $this->filePublicDestination();
        $target_dir = $file_dir . dirname($relative_path);
        if (file_prepare_directory($target_dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
          file_put_contents($file_dir . $relative_path, $response->getBody(TRUE));
          return TRUE;
        }
      }
      return FALSE;
    }
    catch (ClientException $e) {
      // Do nothing.
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function filePublicPath() {
    return 's3fs_to_s3/files';
  }

  /**
   * @return string
   */
  private function filePublicDestination() {
    return 'public://';
  }

  /**
   * {@inheritdoc}
   */
  public function styleOriginalPath($uri, $style_only = TRUE) {
    $scheme = \Drupal::service('file_system')->uriScheme($uri);
    if ($scheme) {
      $path = file_uri_target($uri);
    }
    else {
      $path = $uri;
    }

    // It is a styles path, so we extract the different parts.
    if (strpos($path, 's3fs_to_s3/files/styles') === 0) {
      // Then the path is like styles/[style_name]/[schema]/[original_path].
      return preg_replace('/s3fs_to_s3\/files\/styles\/.*\/(.*)\/(.*)/U', '$1://$2', $path);
    }
    return parent::styleOriginalPath($uri, $style_only);
  }

}
