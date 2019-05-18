<?php

namespace Drupal\flickr\Service;

use Drupal\flickr_api\Service\Photosets as FlickrApiPhotosets;
use Drupal\flickr_api\Service\Helpers as FlickrApiHelpers;

/**
 * Class Photosets.
 *
 * @package Drupal\flickr\Service
 */
class Photosets {

  /**
   * Photosets constructor.
   *
   * @param \Drupal\flickr_api\Service\Photosets $flickrApiPhotosets
   *   API Photosets.
   * @param \Drupal\flickr\Service\Photos $photos
   *   Photos.
   * @param \Drupal\flickr\Service\Helpers $helpers
   *   Helpers.
   * @param \Drupal\flickr_api\Service\Helpers $flickrApiHelpers
   *   API Helpers.
   */
  public function __construct(FlickrApiPhotosets $flickrApiPhotosets,
                              Photos $photos,
                              Helpers $helpers,
                              FlickrApiHelpers $flickrApiHelpers) {
    // Flickr API Photosets.
    $this->flickrApiPhotosets = $flickrApiPhotosets;

    // Flickr Photos.
    $this->photos = $photos;

    // Flickr Helpers.
    $this->helpers = $helpers;

    // Flickr API Helpers.
    $this->flickrApiHelpers = $flickrApiHelpers;
  }

  /**
   * Theme Photos.
   *
   * @param array $photos
   *   Photos.
   * @param string $title
   *   Title.
   *
   * @return array
   *   Theme Array.
   */
  public function themePhotoset(array $photos, $title) {
    return [
      '#theme' => 'flickr_photoset',
      '#photos' => $photos,
      '#title' => $title,
      '#attached' => [
        'library' => [
          'flickr/flickr.stylez',
        ],
      ],
    ];
  }

}
