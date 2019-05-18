<?php

namespace Drupal\media_skyfish;

use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * Service that connects to and get data from Skyfish.
 *
 * @package Drupal\media_skyfish
 */
class ApiService {

  /**
   * Base url for service.
   */
  public const API_BASE_URL = 'https://api.colourbox.com';

  /**
   * Folders uri.
   */
  public const API_URL_FOLDER = '/folder';

  /**
   * Uri for searching folders.
   */
  public const API_URL_SEARCH = '/search?&return_values=title+unique_media_id+thumbnail_url&folder_ids=';

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Header for authorization.
   *
   * @var bool|string
   */
  protected $header;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Drupal logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Cache limit in minutes.
   *
   * @var int|null
   */
  protected $cache;

  /**
   * Construct ApiService.
   *
   * @param \GuzzleHttp\Client $client
   *   Http client.
   * @param \Drupal\media_skyfish\ConfigService $config_service
   *   Config service for Skyfish API authorization and settings.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Drupal user account interface.
   * @param \Psr\Log\LoggerInterface $logger
   *   Loger service.
   */
  public function __construct(Client $client, ConfigService $config_service, AccountInterface $account, LoggerInterface $logger) {
    $this->config = $config_service;
    $this->client = $client;
    $this->header = $this->getHeader();
    $this->user = $account;
    $this->cache = $this->config->getCacheTime();
    $this->logger = $logger;
  }

  /**
   * Get token from a Skyfish.
   *
   * @return string|bool
   *   Authorization token string or false if there was an error.
   */
  public function getToken() {
    try {
      $request = $this
        ->client
        ->request('POST',
          self::API_BASE_URL . '/authenticate/userpasshmac',
          [
            'json' =>
              [
                'username' => $this->config->getUsername() ?? '',
                'password' => $this->config->getPassword() ?? '',
                'key' => $this->config->getKey() ?? '',
                'ts' => time(),
                'hmac' => $this->config->getHmac() ?? '',
              ],
          ]);
      $response = json_decode($request->getBody()->getContents(), TRUE);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    if ($request->getStatusCode() !== 200) {
      $this->handleRequestError($request->getStatusCode());
      return FALSE;
    }

    return $response['token'] ?? FALSE;
  }

  /**
   * Get authorization header.
   *
   * @return bool|string
   *   Authorization header for further communication, or false if error.
   */
  public function getHeader() {
    $token = $this->getToken();

    if (!$token) {
      return FALSE;
    }

    return 'CBX-SIMPLE-TOKEN Token=' . $token;
  }

  /**
   * Make request to Skyfish API.
   *
   * @param string $uri
   *   Request URL.
   *
   * @return array|bool
   *   Response body content.
   */
  protected function doRequest(string $uri) {
    try {
      $request = $this
        ->client
        ->request(
          'GET',
          self::API_BASE_URL . $uri,
          [
            'headers' => [
              'Authorization' => $this->header,
            ],
          ]
        );
    }
    catch (\Exception $e) {
      return FALSE;
    }

    if ($request->getStatusCode() !== 200) {
      $this->handleRequestError($request->getStatusCode());
      return FALSE;
    }

    return json_decode($request->getBody());
  }

  /**
   * Log error on request error.
   *
   * @param int $status_code
   *   HTTP status code.
   */
  protected function handleRequestError(int $status_code) {
    $messages = [
      400 => 'Your request contains bad syntax and the API could not understand it.',
      401 => 'You need to be logged in to access the resource',
      403 => 'You do not have access to this resource. It will help to authenticate.',
      404 => 'The requested resource does not exist. This is also returned if the method is not allowed on the resource.',
      409 => 'We encountered a conflict when trying to process your update. Try applying your update again.',
      500 => 'We encountered a problem parsing your request and can not say what went wrong. Please provide us with the “X-Cbx-Request-Id” from the response as it will help us debug the problem.',
    ];

    if (isset($messages[$status_code])) {
      $this->logger->error($messages[$status_code]);
    }
  }

  /**
   * Get media cached folders from Skyfish API.
   *
   * @return array $folders
   *   Array of Skyfish folders.
   */
  public function getFolders() {
    $cache_id = 'folders_' . $this->user->id();

    $cache = \Drupal::cache()->get($cache_id);
    if (empty($cache->data)) {
      $folders = $this->getFoldersWithoutCache();

      if (!empty($folders)) {
        \Drupal::cache()->set($cache_id, $folders, $this->cache);
      }

      return $folders;
    }

    return $cache->data ?? [];
  }

  /**
   * Get folders from Skyfish API.
   *
   * @return array $folders
   *   Array of Skyfish folders.
   */
  public function getFoldersWithoutCache() {
    $folders = $this->doRequest(self::API_URL_FOLDER);
    return $folders;
  }

  /**
   * Get all images in a Skyfish folder.
   *
   * @return array $images
   *   Array of images in a folder.
   */
  public function getImagesInFolder(int $folder_id) {
    $cache_id = 'images_' . $folder_id . '_' . $this->user->id();
    $cache = \Drupal::cache()->get($cache_id);
    if (empty($cache->data)) {
      $images = $this->getImagesInFolderWithoutCache($folder_id);

      if (!empty($images)) {
        \Drupal::cache()->set($cache_id, $images, $this->cache);
      }

      return $images;
    }

    return $cache->data ?? [];
  }

  /**
   * Get images from Skyfish API.
   *
   * @param int $folder_id
   *   Id of the folder.
   *
   * @return array $images
   *   Array of images in a folder.
   */
  public function getImagesInFolderWithoutCache(int $folder_id) {
    $response = $this->doRequest(self::API_URL_SEARCH . $folder_id);
    $images = $response->response->media ?? [];

    return $images;
  }

  /**
   * Store images with metadata.
   *
   * @param array $images
   *   Loaded images.
   *
   * @return array $images
   *   Array of images with metadata.
   */
  public function getImagesMetadata(array $images) {
    foreach ($images as $image_id => $image) {
      if (($metadata = $this->getImageMetadata($image)) === FALSE) {
        unset($images[$image_id]);
      }

      $images[$image_id] = $metadata;
    }

    return $images;
  }

  /**
   * Set metadata for the image.
   *
   * @param \stdClass $image
   *   Skyfish image.
   *
   * @return bool|\stdClass $image
   *   If image title empty display Skyfish id.
   */
  public function getImageMetadata(\stdClass $image) {
    $image->title = $this->getImageTitle($image->unique_media_id);
    $image->download_url = $this->getImageDownloadUrl($image->unique_media_id);
    $image->filename = $this->getFilename($image->unique_media_id);

    if ($image->download_url === FALSE) {
      $this->logger('Image (@image) does not exist. @url does not exist', [
        '@image' => $image->unique_media_id,
        '@url' => $image->download_url,
      ]);

      drupal_set_message(
        $this->t('Image does not exist. Image download url does not exist.', [
          '@image' => $image->unique_media_id,
          '@url' => $image->download_url,
        ]),
        'error'
      );

      return FALSE;
    }

    if ($image->title === FALSE) {
      $image->title = $image->unique_media_id;
    }

    return $image;
  }

  /**
   * Get image title.
   *
   * @param int $img_id
   *   Id of the image.
   *
   * @return string $filename
   *   Filename of the image.
   */
  public function getImageTitle(int $img_id) {
    $request = $this->doRequest('/media/' . $img_id);
    $full_filename = $request->filename;
    $filename = substr($full_filename, 0, (strrpos($full_filename, '.')));

    return $filename;
  }

  /**
   * Get filename.
   *
   * @param int $img_id
   *   Id of the image.
   *
   * @return string
   *   Filename.
   */
  public function getFilename(int $img_id) {
    $request = $this->doRequest('/media/' . $img_id);

    return $request->filename;
  }

  /**
   * Get image download url.
   *
   * @param int $img_id
   *   Id of the image.
   *
   * @return string
   *   Download url.
   */
  public function getImageDownloadUrl(int $img_id) {
    $request = $this->doRequest('/media/' . $img_id . '/download_location');

    return $request->url;
  }

}
