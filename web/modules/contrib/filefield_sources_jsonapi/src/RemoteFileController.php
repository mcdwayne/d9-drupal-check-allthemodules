<?php

namespace Drupal\filefield_sources_jsonapi;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\Component\Utility\Unicode;

/**
 * Retrieve file via curl call for filefield source JSON API.
 */
class RemoteFileController extends ControllerBase {

  /**
   * Get file via curl and return it.
   */
  public static function getRemoteFile() {
    $myConfig = \Drupal::config('filefield_sources_jsonapi');
    $username = $myConfig->get('username');
    $password = $myConfig->get('password');

    $url = \Drupal::request()->query->get('url');
    $temporary_directory = 'temporary://';
    $url_info = parse_url($url);
    $filename = rawurldecode(basename($url_info['path']));
    $filepath = file_create_filename($filename, $temporary_directory);

    $fp = @fopen($filepath, 'w');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    // Causes a warning if PHP safe mode is on.
    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_FILE, $fp);

    $file_contents = curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    if ($file_contents !== NULL) {
      $mime = \Drupal::service('file.mime_type.guesser')->guess($filepath);
      $headers = [
        'Content-Type' => $mime . '; name="' . Unicode::mimeHeaderEncode(basename($filepath)) . '"',
        'Content-Length' => filesize($filepath),
        'Cache-Control' => 'private',
      ];
      return new BinaryFileResponse($filepath, 200, $headers, FALSE);
    }

    throw new NotFoundHttpException();
  }

}
