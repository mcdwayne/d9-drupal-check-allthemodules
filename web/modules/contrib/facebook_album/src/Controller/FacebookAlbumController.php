<?php
/**
 * @file
 * Contains \Drupal\facebook_album\Controller\FacebookAlbumController.
 */

namespace Drupal\facebook_album\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\block\BlockInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\facebook_album\FacebookAlbumInterface;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Controller for Facebook Album.
 */
class FacebookAlbumController extends ControllerBase {

  /**
   * The FB Album controller.
   *
   * @var FacebookAlbumInterface
   */
  protected $facebook_album;

  /**
   * @param FacebookAlbumInterface $facebook_album
   *   The controls of facebook album.
   */
  public function __construct(FacebookAlbumInterface $facebook_album) {
    $this->facebook_album = $facebook_album;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('facebook_album.controller')
    );
  }

  /**
   * Fetch first set of albums specified in the settings menu
   *
   * @param \Drupal\block\BlockInterface $block
   * @return mixed
   */
  public function getAlbums(BlockInterface $block) {
    return $this->getAlbumsNext($block);
  }

  /**
   * Fetch the next or previous set of cover photos from the specified page ID
   *
   * @param \Drupal\block\BlockInterface $block
   * @param null $after
   *  The id for fetching the next set of albums
   * @return \Zend\Diactoros\Response\JsonResponse
   *  A json object containing an html template and after id
   */
  public function getAlbumsNext(BlockInterface $block, $after = NULL) {
    $settings = $block->get('settings');
    $limit = $settings['album_limit'];

    if ($limit < 1) {
      $limit = NULL;
    }
    else {
      // ensure that ID's can't be passed in to retrieve albums
      // if limit has been set to a non-zero number
      $after = NULL;
    }

    $path = $settings['page_id'] . '/albums';
    $parameters = [
      'after' => $after,
      'limit' => $limit,
      'fields' => 'location,description,name,cover_photo.fields(images)'
    ];

    $response = $this->makeRequest($path, $parameters);

    // Filter out any albums from the config
    $filtered_content = $this->filterAlbums($response['data'], $settings['albums'], $settings['album_visibility']);

    // Build json response
    $json_response = [];
    $render = [
      '#theme' => 'facebook_album_covers',
      '#settings' => $settings,
      '#photos' => $filtered_content,
    ];

    $json_response['data']['content'] = \Drupal::service('renderer')->render($render);

    if (isset($response['paging']) && isset($response['paging']['next']) && $limit == NULL) {
      $json_response['data']['after'] = $response['paging']['cursors']['after'];
    }
    else {
      $json_response['data']['after'] = NULL;
    }
    return new JsonResponse($json_response);
  }

  /**
   * Fetch the first set of photos from the specified album
   *
   * @param \Drupal\block\BlockInterface $block
   * @param $album_id
   *  The album id to fetch photos from
   * @return mixed
   */
  public function getAlbum(BlockInterface $block, $album_id) {
    return $this->getAlbumNext($block, $album_id);
  }

  /**
   * Fetch the next or previous set of photos from the specified album
   *
   * @param \Drupal\block\BlockInterface $block
   * @param $album_id
   *  The album id to fetch photos from
   * @param null $after
   *  The id for fetching the next or previous set of photos
   * @return \Zend\Diactoros\Response\JsonResponse
   */
  public function getAlbumNext(BlockInterface $block, $album_id, $after = NULL) {
    $settings = $block->get('settings');

    $parameters = [
      'after' => $after,
      'fields' => 'url'
    ];

    $response = $this->makeRequest($album_id . '/photos', $parameters);

    // Build json response
    $json_response = [];
    $render = [
      '#theme' => 'facebook_album_photos',
      '#settings' => $settings,
      '#photos' => $response['data'],
    ];

    $json_response['data']['content'] = \Drupal::service('renderer')->render($render);
    $json_response['data']['photo_ids'] = $response['data'];

    if (isset($response['paging']) && isset($response['paging']['next'])) {
      $json_response['data']['after'] = $response['paging']['cursors']['after'];
    }

    return new JsonResponse($json_response);
  }

  /**
   * Fetch an individual photo url from a Facebook album photo
   *
   * @param $photo_id
   * @return \Zend\Diactoros\Response\JsonResponse
   */
  public function getPhoto($photo_id) {
    $json_response = array('data' => NULL);

    $parameters = [
      'fields' => 'images,name'
    ];

    $response = $this->makeRequest($photo_id, $parameters);

    if (!isset($response['error'])) {
      $json_response['data']['url'] = $response['images'][0]['source'];
      $json_response['data']['name'] = isset($response['name']) ? $response['name'] : '';
    }

    return new JsonResponse($json_response);
  }

  /**
   * The Facebook API does not allow us to specify which albums to load or
   * exclude so after loading the albums we'll simply filter for any albums
   * we want to display
   *
   * @param $albums
   *    An array of albums from the facebook API
   * @param array $album_ids
   *    Album IDs used to determine a whitelist or blacklist of albums from
   * @param bool $include
   *    A flag, that if true, includes all albums specified in $albumIDs, otherwise
   *    it excludes all albums in $albumIDs
   * @return array
   *    An array of filtered albums
   */
  protected function filterAlbums($albums, $album_ids = [], $include = TRUE) {
    if (isset($album_ids[0]) && ($album_ids[0] != '' || $album_ids[0] == 0)) {
      $include = (bool) $include;
      $albums = array_filter($albums, function ($album) use ($album_ids, $include) {
        return $include === in_array($album['id'], $album_ids);
      });
    }
    return $albums;
  }

  /**
   * Makes and Caches responses to limit FB API traffic
   *
   * @param $path
   * @param array $parameters
   *
   * @return mixed
   */
  protected function makeRequest($path, $parameters = []) {
    // For consistency
    $cid = 'fba:' . str_replace("/", ":", $path);

    // Append params except non-data changers
    foreach ($parameters as $key => $parameter) {
      if ($key != 'fields' && !empty($parameter)) {
        $cid .= ':' . $parameter;
      }
    }

    // Check cache first before calling API
    if ($cache = \Drupal::cache()->get($cid)) {
      $response = $cache->data;
    }
    else {
      $response = $this->facebook_album->get($path, $parameters);
      if (!isset($response['error'])) {
        \Drupal::cache()->set($cid, $response);
      }
    }

    return $response;
  }

}
