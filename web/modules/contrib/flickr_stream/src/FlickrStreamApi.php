<?php

namespace Drupal\flickr_stream;

use Drupal;
use GuzzleHttp\Exception\ClientException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;

/**
 * Class FlickrStreamApi.
 *
 * @package Drupal\FlickrStreamApi
 */
class FlickrStreamApi {

  const FLICKR_API_URL = 'https://api.flickr.com/services/rest/';
  protected $flickrConf;

  /**
   * Returns generic default configuration for flickr api.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  protected function baseConfigurationDefaults() {
    $flickConfig = Drupal::config('flickr_stream.settings');
    return [
      'api_key' => $flickConfig->get('flickr_stream_api_key'),
      'default_count' => $flickConfig->get('flickr_stream_photo_count'),
      'uri' => Url::fromUri(self::FLICKR_API_URL)->toUriString(),
    ];
  }

  /**
   * FlickrStreamApi set configurations.
   *
   * @param string $userId
   *   Flickr user Id.
   * @param string $photosetId
   *   Flickr album Id.
   * @param string $photoCount
   *   Flickr photo count.
   *
   * @return array
   *   Define conf array.
   */
  public function setConfig($userId, $photosetId, $photoCount = NULL) {
    $defaultConf = $this->baseConfigurationDefaults();
    $photoCount = ($photoCount) ?: $defaultConf['default_count'];
    $this->flickrConf = NestedArray::mergeDeep(
      $defaultConf,
      [
        'photoset_id' => $photosetId,
        'user_id' => $userId,
        'default_count' => $photoCount,
      ]
    );
    return $this->flickrConf;
  }

  /**
   * Generate photo uri from flickr api result.
   *
   * @param array $flickr_photo
   *   Flickr api result array.
   *
   * @return string
   *   Uri to image in flickr.
   */
  public function generatePhotoUri(array $flickr_photo) {
    return 'https://farm' . $flickr_photo['farm'] .
      '.staticflickr.com/' . $flickr_photo['server'] .
      '/' . $flickr_photo['id'] .
      '_' . $flickr_photo['secret'] . '_b.jpg';
  }

  /**
   * Helper function to build images markup.
   *
   * @param array $flickrImages
   *   Flickr images array fron api.
   * @param string $apiType
   *   Api type to build images.
   * @param array $image_style
   *   Images output style.
   *
   * @return string
   *   Build images render html.
   */
  public function flickrBuildImages(array $flickrImages, $apiType, array $image_style) {
    $images_markup = '';
    // Detect flickr images type.
    $flickr_array = ($apiType == 'album') ? $flickrImages['photoset']['photo'] : $flickrImages['photos']['photo'];
    $images_markup .= '<div class="flick-image-wrapper">';
    foreach ($flickr_array as $index => $flickr_photo) {
      switch ($image_style['flickr_images_style']) {
        case 'default':
          $cached_images = imagecache_external_generate_path($this::generatePhotoUri($flickr_photo));
          $images_markup .= '<div class="flickr-image" ><img src="' . file_create_url($cached_images) . '" alt="' . $flickr_photo['title'] . '" /></div>';
          break;

        default:
          $cached_images = [
            '#theme' => 'imagecache_external',
            '#style_name' => $image_style['flickr_images_style'],
            '#uri' => $this::generatePhotoUri($flickr_photo),
            '#alt' => $flickr_photo['title'],
          ];
          $images_markup .= '<div class="flickr-image" >' . render($cached_images) . '</div>';
      }
    }
    $images_markup .= '</div>';
    return $images_markup;
  }

  /**
   * Get photos from album flickr APIs.
   *
   * @param array $conf
   *   FlickrStreamApi configurations.
   *
   * @return array
   *   Flickr API result.
   */
  public function getAlbumPhotos(array $conf) {
    $flickr_results = [];
    $client = Drupal::httpClient();
    try {
      $request = $client->get($conf['uri'], [
        'query' => [
          'method' => 'flickr.photosets.getPhotos',
          'api_key' => $conf['api_key'],
          'photoset_id' => $conf['photoset_id'],
          'user_id' => $conf['user_id'],
          'format' => 'json',
          'nojsoncallback' => 1,
          'per_page' => $conf['default_count'],
        ],
      ]);
      $response = $request->getBody();
      $flickr_results = json_decode($response->read($response->getSize()), TRUE);
      if ($flickr_results['stat'] == 'fail') {
        Drupal::logger('flickr_stream')->notice('Flickr api get @errorId error with message: @errorMessage', [
          '@errorId' => $flickr_results['stat'],
          '@errorMessage' => $flickr_results['message'],
        ]);
      }
    }
    catch (ClientException $exception) {
      Drupal::logger('flickr_stream')->notice($exception);
      Drupal::logger('flickr_stream')->alert('Please check flickrs credentials and flickr fields inputs. Go to logs for more information');
    }
    return $flickr_results;
  }

  /**
   * Get photos from users flickr APIs.
   *
   * @param array $conf
   *   FlickrStreamApi configurations.
   *
   * @return array
   *   Flickr API result.
   */
  public function getUserPhotos(array $conf) {
    $flickr_results = [];
    $client = Drupal::httpClient();
    try {
      $request = $client->get($conf['uri'], [
        'query' => [
          'method' => 'flickr.people.getPublicPhotos',
          'api_key' => $conf['api_key'],
          'user_id' => $conf['user_id'],
          'format' => 'json',
          'nojsoncallback' => 1,
          'per_page' => $conf['default_count'],
        ],
      ]);
      $response = $request->getBody();
      $flickr_results = json_decode($response->read($response->getSize()), TRUE);
      if ($flickr_results['stat'] == 'fail') {
        Drupal::logger('flickr_stream')->notice('Flickr api get @errorId error with message: @errorMessage', [
          '@errorId' => $flickr_results['stat'],
          '@errorMessage' => $flickr_results['message'],
        ]);
      }
    }
    catch (ClientException $exception) {
      Drupal::logger('flickr_stream')->notice($exception);
      Drupal::logger('flickr_stream')->alert('Please check flickrs credentials and flickr fields inputs. Go to logs for more information');
    }
    return $flickr_results;
  }

}
