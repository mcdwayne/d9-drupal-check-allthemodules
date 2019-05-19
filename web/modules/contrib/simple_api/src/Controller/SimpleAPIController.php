<?php

/**
 * @file
 * Contains \Drupal\simple_api\Controller\SimpleAPIController.
 */

namespace Drupal\simple_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for simple_api routes.
 */
class SimpleAPIController extends ControllerBase {

  /**
   * Callback for `api/simple/{directory}/{item_id}` API method.
   * @param string $directory
   *    This is the directory inside of the public filesystem that contains the JSON files for the API.
   * @param string $item_id
   *    This is the file name of the JSON data, eg. {ITEM_ID}.json
   * @return JsonResponse
   */
  public function itemSingle($directory, $item_id) {
    $response = $this->getItem($directory, $item_id);
    return new CacheableJsonResponse( $response );
  }

  /**
   * Callback for `api/simple/{directory}` API method.
   * @param string $directory
   * @return JsonResponse
   *    A full JSON list of the data compiled from the files in the directory.
   */
  public function itemList($directory) {
    $response = $this->generateItemList($directory);
    return new CacheableJsonResponse( $response );
  }

  /**
   * Helper function to generate the list of items by scanning the directory and compiling the files.
   * @param string $directory
   * @return array $list
   */
  public function generateItemList($directory) {
    $path = $this->getPath($directory);
    $files = new \FilesystemIterator($path);
    $list = [];
    foreach ($files as $file) {
      $item_id = str_replace('.json', '', strtolower($file->getFilename()));
      $list[$item_id] = $this->getItem($directory, $item_id);
    }
    return ($files) ? $list : ['error' => "No $directory data found."];
  }

  /**
   * Helper function to grab a single item.
   * @param string $directory
   * @param $item_id
   * @return array $list
   */
  public function getItem($directory, $item_id) {
    $file = $this->getPath($directory) . "/$item_id.json";
    if (file_exists($file)) {
      $data = json_decode(file_get_contents($file), true);
    }
    return (isset($data)) ? $data : ['error' => "No data for $item_id found."];
  }

  /**
   * Helper function to provide the directory we need for this request.
   * @param string $directory
   * @return string $path
   */
  public function getPath($directory) {
    $files_dir = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    return $files_dir . '/' . $directory;
  }

}
