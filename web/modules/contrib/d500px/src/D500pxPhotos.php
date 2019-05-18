<?php

namespace Drupal\D500px;

/**
 * Drupal 500px Photos class.
 *
 * @package Drupal\D500px
 */
class D500pxPhotos {

  /**
   * Helpers.
   *
   * @var \Drupal\d500px\D500pxHelpers
   */
  public $d500pxhelpers;

  /**
   * Integration Class.
   *
   * @var \Drupal\d500px\D500pxIntegration
   */
  protected $d500pxintegration;

  /**
   * D500pxPhotos constructor.
   *
   * @param \Drupal\D500px\D500pxHelpers $d500pxhelpers
   *   Helpers.
   * @param \Drupal\D500px\D500pxIntegration $d500pxintegration
   *   Integration.
   */
  public function __construct(D500pxHelpers $d500pxhelpers, D500pxIntegration $d500pxintegration) {
    $this->d500pxhelpers = $d500pxhelpers;
    $this->d500pxintegration = $d500pxintegration;
  }

  /**
   * Helper method to get photos.
   *
   * @param array $parameters
   *   Params.
   * @param bool $nsfw
   *   Not Safe bool.
   *
   * @return array
   *   Themed photos.
   */
  public function getPhotos(array $parameters = [], $nsfw = FALSE) {
    $photos = $this->d500pxintegration->requestD500px('photos', $parameters)->photos;
    $themed_photos = NULL;

    foreach ($photos as $photo_obj) {
      $photo_obj->photo_page_url = $this->d500pxintegration->website_url . $photo_obj->url;
      $themed_photos[] = $this->d500pxhelpers->preparePhoto($photo_obj, $nsfw);
    }

    return ['#theme' => 'd500px_photos', '#photos' => $themed_photos];
  }

  /**
   * Helper method to get photo by id.
   *
   * @param string $photoid
   *   PhotoID.
   * @param array $parameters
   *   Params.
   * @param bool $nsfw
   *   Not Safe bool.
   *
   * @return array
   *   Themed photos.
   */
  public function getPhotoById($photoid, array $parameters = [], $nsfw = FALSE) {
    $photo_obj = $this->d500pxintegration->requestD500px('photos/' . $photoid, $parameters)->photo;
    $photo_obj->photo_page_url = $this->d500pxintegration->website_url . $photo_obj->url;
    $themed_photo = $this->d500pxhelpers->preparePhoto($photo_obj, $nsfw);
    return $themed_photo;
  }

}
