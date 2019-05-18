<?php

namespace Drupal\s3fs_plus\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Aws\S3\S3Client;

/**
 * Defines a route controller for s3fs autocomplete.
 */
class AutocompleteController extends ControllerBase {

  /**
   * The S3 Object.
   *
   * @var Aws\S3\S3Client
   */
  protected $s3;

  /**
   * The config object for s3fs.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->config = \Drupal::config('s3fs.settings');
    $this->getClient();
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $path = $request->query->get('q');
    $paths = [];

    // Get the typed string from the URL, if it exists.
    if (!empty($path)) {
      $objects = $this->s3->listObjectsV2(['Bucket' => $this->config->get('bucket'), 'Prefix' => $path]);
      $contents = $objects->toArray()['Contents'];
      $keys = [];
      foreach ($contents as $content) {
        $key = explode('/', $content['Key']);
        // Remove filename from the array.
        // If its one value in the array, this is a file, not a directory.
        array_pop($key);
        $key = implode('/', $key);
        // Make sure results are unique, and blank keys are rejected.
        if (!empty($key) && !in_array($key, $keys)) {
          $paths[] = [
            'value' => $key,
            'label' => $key,
          ];
          // Populate another array to check uniqueness.
          $keys[] = $key;
          // Break loop, after we have 10 unique paths.
          if (count($keys) == 10) {
            break;
          }
        }
      }
    }

    return new JsonResponse($paths);
  }

  /**
   * Create a new S3 object.
   */
  protected function getClient() {
    if (!empty($this->config)) {
      $client = S3Client::factory(array(
        'credentials' => array(
          'key' => $this->config->get('access_key'),
          'secret' => $this->config->get('secret_key'),
        ),
        'region' => $this->config->get('region'),
        'version' => 'latest',
      ));
      $this->s3 = $client;
    }
  }

}
