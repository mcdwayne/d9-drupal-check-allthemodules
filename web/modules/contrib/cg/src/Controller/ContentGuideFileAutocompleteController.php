<?php

namespace Drupal\cg\Controller;

/**
 * @file
 * Ajax Autocomplete Callback for markdown files in Content Guide.
 */

use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Html;

/**
 * Returns markdown files in autocomplete.
 *
 * @package Drupal\cg\Controller
 */
class ContentGuideFileAutocompleteController {

  /**
   * Autocomplete for searching markdown files.
   *
   * Searches the configured directory and subdirectory for markdown
   * files to attach them to fields in form display and store them in a cache.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request made from the autocomplete widget.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A json response to use in the autocomplete widget.
   */
  public function autocomplete(Request $request) {

    $request_filename = HTML::escape($request->query->get('q'));

    // Get the directory of the stored files.
    $config = \Drupal::config('cg.settings');
    $document_base_path = $config->get('document_base_path');
    $base_path = \Drupal::service('file_system')
      ->realpath(\DRUPAL_ROOT . '/' . $document_base_path);

    // Get markdown files in configured path. Load from cache if possible.
    $files = $this->getFiles($base_path);

    // Scan all files and return files where the autocomplete entry exists.
    $autocomplete_files = [];
    foreach ($files as $file_parts) {
      // Split the string to only return the subdirectory and filename.
      $file_path = str_replace($base_path, '', $file_parts['dirname']) . '/' . $file_parts['basename'];
      $file_path = ltrim(rtrim($file_path, '/'), '/');
      if (strpos($file_path, $request_filename) === FALSE) {
        continue;
      }
      $autocomplete_files[] = [
        'value' => $file_path,
        'label' => $file_path,
      ];
    }
    $response_data = new JsonResponse();
    $response_data->setContent(json_encode($autocomplete_files));
    return $response_data;
  }

  /**
   * Load markup files from configured path.
   *
   * @param string $path
   *   The path to the markdown files.
   *
   * @return array
   *   A list of markdown files.
   */
  protected function getFiles($path) {
    $cached_files = \Drupal::cache('data')->get('cg_files');
    if (!empty($cached_files)) {
      return $cached_files->data;
    }
    $files = [];
    // Loop recursively trough the directories and build the autocomplete array.
    $di = new \RecursiveDirectoryIterator($path);
    foreach (new \RecursiveIteratorIterator($di) as $filename => $file) {
      $file_parts = pathinfo($filename);
      // Only get markdown files.
      if (!empty($file_parts['extension'])) {
        switch ($file_parts['extension']) {
          case "md":
            $files[] = $file_parts;
          default:
            break;
        }
      }
    }
    // Cache files for later usage.
    \Drupal::cache('data')->set(
      'cg_files',
      $files,
      CacheBackendInterface::CACHE_PERMANENT,
      ['content_guide']
    );
    return $files;
  }

}
