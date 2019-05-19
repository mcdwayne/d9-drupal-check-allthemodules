<?php

namespace Drupal\vidyard\OEmbed;

use Drupal\media\OEmbed\ResourceFetcher;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\ProviderRepositoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\ClientInterface;

/**
 * Fetches and caches oEmbed resources.
 */

/**
 * Class for customizing the Media oEmbed resource fetcher.
 *
 * Decorates the Media oEmbed ResourceFetcher with createResource() modified to
 * add missing thumbnail information when creating the resource.
 *
 * @package Drupal\vidyard\OEmbed
 */
class VidyardResourceFetcher extends ResourceFetcher {

  /**
   * @inheritdoc
   */
  public function __construct(ResourceFetcherInterface $inner_service, ClientInterface $http_client, ProviderRepositoryInterface $providers, CacheBackendInterface $cache_backend = NULL) {
    $this->innerService = $inner_service;
    parent::__construct($http_client, $providers, $cache_backend = NULL);
  }

  /**
   * Creates a Resource object from raw resource data and adds missing thumbnail
   * information.
   *
   * @see \Drupal\media\OEmbed\ResourceFetcher::createResource()
   */
  protected function createResource(array $data, $url) {

    // Vidyard's oEmbed implementation does not return the URL to the video thumbnail.
    // Here we parse the oEmbed resource url and extract the Vidyard video id so we can
    // use it to cobble together a thumbnail URL.
    $query_string = parse_url($url, PHP_URL_QUERY);
    parse_str($query_string, $query_values);

    $video_id = $this->getIdFromUrl($query_values['url']);

    if ($video_id) {
      // The thumbnail dimensions are required when saving the resource. Since we
      // don't have that info yet, just use the video dimensions that were
      // returned with the oEmbed query result.
      $data += [
        'thumbnail_url' => sprintf("https://play.vidyard.com/%s.jpg", $video_id),
        'thumbnail_width' => $data['width'],
        'thumbnail_height' => $data['height'],
      ];
    }

    return $this->innerService->createResource($data, $url);
  }

  /**
   * Get the Vidyard video ID from the video URL.
   *
   * @param string $url
   *   The URL for the Vidyard video.
   *
   * @return mixed
   *   Returns a string containing the video id or FALSE if and ID is not found.
   */
  public static function getIdFromUrl($url) {
    $reg = "/(http(s?):)?\/\/[a-zA-Z0-9]+\.vidyard.com\/(?:share|watch)?(\/)?(?<id>[a-zA-Z0-9_-]+)/";
    preg_match($reg, $url, $matches);

    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
