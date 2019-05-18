<?php

namespace Drupal\flickr\Service;

use Drupal\flickr_api\Service\Photos as FlickrApiPhotos;
use Drupal\flickr_api\Service\Helpers as FlickrApiHelpers;

/**
 * Class Photos.
 *
 * @package Drupal\flickr\Service
 */
class Photos {

  /**
   * Photos constructor.
   *
   * @param \Drupal\flickr_api\Service\Photos $flickrApiPhotos
   *   API Photos.
   * @param \Drupal\flickr\Service\Helpers $helpers
   *   Helpers.
   * @param \Drupal\flickr_api\Service\Helpers $flickrApiHelpers
   *   API Helpers.
   */
  public function __construct(FlickrApiPhotos $flickrApiPhotos,
                              Helpers $helpers,
                              FlickrApiHelpers $flickrApiHelpers) {
    // Flickr API Photos.
    $this->flickrApiPhotos = $flickrApiPhotos;

    // Flickr Helpers.
    $this->helpers = $helpers;

    // Flickr API Helpers.
    $this->flickrApiHelpers = $flickrApiHelpers;

  }

  /**
   * Theme Photos.
   *
   * @param array $photo
   *   Photo.
   * @param string $size
   *   Size.
   * @param int $caption
   *   Caption On Off.
   * @param string $parent
   *   Parent.
   *
   * @return array
   *   Return theme array.
   */
  public function themePhoto(array $photo, $size, $caption = 0, $parent = NULL) {
    $photoSize = $this->photoGetSize($photo['id'], $size);
    $photoSizeLarge = $this->photoGetSize($photo['id'], 'b');

    if ($photoSize != FALSE) {
      $img = [
        '#theme' => 'image',
        '#style_name' => 'flickr-photo-' . $size . '-' . $photoSize['aspect'],
        '#uri' => $this->flickrApiHelpers->photoImgUrl($photo, $size),
        '#alt' => $photo['title']['_content'] . ' by ' . $photo['owner']['realname'],
        '#title' => $photo['title']['_content'] . ' by ' . $photo['owner']['realname'],
        '#attributes' => [
          'width' => $photoSize['width'],
          'height' => $photoSize['height'],
          // @codingStandardsIgnoreStart
          // 'style' => 'width: ' . $photoSize['width'] . 'px; height: ' . $photoSize['width'] . 'px;',.
          // @codingStandardsIgnoreEnd
        ],
      ];

      $photoimg = [
        '#theme' => 'flickr_photo',
        '#photo' => $img,
        '#caption' => $caption,
        '#photo_page_url' => $photo['urls']['url'][0]['_content'],
        '#photo_image_large' => $photoSizeLarge['source'],
        '#parent' => $parent,
        '#style_name' => 'flickr-photo-' . $size . '-' . $photoSize['aspect'],
        '#width' => $photoSize['width'],
        '#height' => $photoSize['height'],
        '#attached' => [
          'library' => [
            'flickr/flickr.stylez',
          ],
        ],
      ];

      if ($caption == 1) {
        $photoimg['#caption_data'] = $this->themeCaption($photo, $size, $caption);
      }

      return $photoimg;
    }
  }

  /**
   * Theme Photos.
   *
   * @param array $photos
   *   Photos.
   * @param string $size
   *   Size.
   * @param int $caption
   *   Caption On Off.
   * @param string $parent
   *   Parent.
   *
   * @return array
   *   Return theme array.
   */
  public function themePhotos(array $photos, $size, $caption = 0, $parent = NULL) {
    foreach ($photos as $photo) {
      $themedPhotos[] = $this->themePhoto(
        $this->flickrApiPhotos->photosGetInfo($photo['id']),
        $size,
        $caption,
        $parent
      );
    }

    return [
      '#theme' => 'flickr_photos',
      '#photos' => $themedPhotos,
      '#attached' => [
        'library' => [
          'flickr/flickr.stylez',
        ],
      ],
    ];
  }

  /**
   * Theme Caption.
   *
   * @param array $photo
   *   Photo.
   * @param string $size
   *   Size.
   * @param int $caption
   *   Caption.
   *
   * @return array
   *   Return theme array.
   */
  public function themeCaption(array $photo, $size, $caption) {
    return [
      '#theme' => 'flickr_photo_caption',
      '#caption' => $caption,
      '#caption_realname' => $photo['owner']['realname'],
      '#caption_title' => $photo['title']['_content'],
      '#caption_description' => $photo['description']['_content'],
      '#caption_dateuploaded' => $photo['dateuploaded'],
      '#style_name' => 'flickr-photo-' . $size,
      '#photo_size' => $size,
    ];
  }

  /**
   * Get Photo Size.
   *
   * @param string $photoId
   *   Photo ID.
   * @param string $size
   *   Size.
   *
   * @return bool|array
   *   False or array.
   */
  public function photoGetSize($photoId, $size) {
    $photoSizes = $this->flickrApiPhotos->photosGetSizes($photoId);
    $sizes = $this->flickrApiHelpers->photoSizes();
    $label = $sizes[$size]['label'];

    foreach ($photoSizes as $size) {
      if ($size['label'] == $label) {
        $size['width'] = (int) $size['width'];
        $size['height'] = (int) $size['height'];
        $size['aspect'] = $this->photoCalculateAspectRatio($size['width'], $size['height']);
        return $size;
      }
    }

    return FALSE;
  }

  /**
   * Calculate aspect.
   *
   * @param int $width
   *   Width.
   * @param int $height
   *   Height.
   *
   * @return string
   *   Return aspect.
   */
  public function photoCalculateAspectRatio($width, $height) {
    $aspectRatio = (int) $width / (int) $height;

    if ($aspectRatio > 1) {
      // Image is Landscape.
      return 'landscape';
    }
    if ($aspectRatio < 1) {
      // Image is Portrait.
      return 'portrait';
    }
    else {
      // Image is Square.
      return 'square';
    }
  }

}
