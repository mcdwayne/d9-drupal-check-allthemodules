<?php

namespace Drupal\flickr_block;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FlickrAPI.
 */
class FlickrAPI implements ContainerInjectionInterface {

  use StringTranslationTrait;

  const URL_API_FLICKR = "https://api.flickr.com/services/rest/";
  const FLICKR_FORMAT = 'php_serial';

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * AbstractDao constructor.
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('http_client'));
  }

  /**
   * Call flickr API.
   *
   * @param array $params
   *   Params to flickr API.
   *
   * @return array|bool
   *   Flickr response.
   */
  public function call(array $params) {

    $url = Url::fromUri(self::URL_API_FLICKR, [
      'query' => $params,
    ])->toUriString();

    try {
      $response = $this->httpClient
        ->get($url)
        ->getBody();
      return unserialize($response);
    }
    catch (\Exception $e) {
      watchdog_exception('flickr_block', $e, 'There was an error requesting a response.');
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhotoSizes() {
    return [
      's' => $this->t('Small square 75x75 px'),
      'q' => $this->t('Large square 150x150 px'),
      't' => $this->t('Thumbnail, 100 px on the longest side'),
      'm' => $this->t('Small, 240 px on the longest side'),
      'n' => $this->t('Small, 320 px on longest side'),
      'z' => $this->t('Medium, 640 px on the longest side'),
      'c' => $this->t('Medium size, 800 px on the longest side'),
      'b' => $this->t('Large, 1024 px on the longest side'),
      'h' => $this->t('Large, 1600 px longest side'),
      'k' => $this->t('Large, 2048 px of the longest side'),
      'o' => $this->t('Original image, either jpg, gif or png, depending on the source format'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function generatePhotoUri($photo, $size) {
    return 'https://farm' . $photo['farm'] . '.staticflickr.com/'
      . $photo['server'] . '/' . $photo['id'] . '_' . $photo['secret']
      . '_' . $size . '.jpg';
  }

  /**
   * {@inheritdoc}
   */
  public function generatePhotoUriFlickr($photo_id,
                                         $user_id) {
    return 'https://www.flickr.com/photos/'
      . $user_id . '/' . $$photo_id . '/in/dateposted/';
  }

  /**
   * {@inheritdoc}
   */
  public function generateParams($conf) {
    $params = [
      'api_key' => $conf['flickr_api_key'],
      'user_id' => $conf['flickr_user_id'],
      'format' => self::FLICKR_FORMAT,
      'per_page' => $conf['flickr_number_photos'],
    ];

    if ($conf['flickr_photoset_id']) {
      $params['method'] = 'flickr.photosets.getPhotos';
      $params['photoset_id'] = $conf['flickr_photoset_id'];
    }
    else {
      $params['method'] = 'flickr.people.getPhotos';
    }
    return $params;
  }

}
