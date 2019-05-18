<?php

namespace Drupal\flickr_api\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class Helpers.
 *
 * @package Drupal\flickr_api\Service
 */
class Helpers {

  use StringTranslationTrait;

  /**
   * Helpers constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String Translation.
   */
  public function __construct(TranslationInterface $stringTranslation) {
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * Dectect NSID.
   *
   * @param string $id
   *   Id.
   *
   * @return false|int
   *   True False.
   */
  public function isNsid($id) {
    return preg_match('/^\d+@N\d+$/', $id);
  }

  /**
   * A list of possible photo sizes with description and label.
   *
   * @return array
   *   An array of photo sizes.
   */
  public function photoSizes() {
    return [
      's' => [
        'label' => 'Square',
        'description' => $this->t('s: 75 px square'),
        'width' => 75,
        'height' => 75,
      ],
      't' => [
        'label' => 'Thumbnail',
        'description' => $this->t('t: 100px on longest side'),
        'width' => 100,
        'height' => 67,
      ],
      'q' => [
        'label' => 'Large Square',
        'description' => $this->t('q: 150px square'),
        'width' => 150,
        'height' => 150,
      ],
      'm' => [
        'label' => 'Small',
        'description' => $this->t('m: 240px on longest side'),
        'width' => 240,
        'height' => 240,
      ],
      'n' => [
        'label' => 'Small 320',
        'description' => $this->t('n: 320px on longest side'),
        'width' => 320,
        'height' => 320,
      ],
      '-' => [
        'label' => 'Medium',
        'description' => $this->t('-: 500px on longest side'),
        'width' => 500,
        'height' => 500,
      ],
      'z' => [
        'label' => 'Medium 640',
        'description' => $this->t('z: 640px on longest side'),
        'width' => 640,
        'height' => 640,
      ],
      'c' => [
        'label' => 'Medium 800',
        'description' => $this->t('c: 800px on longest side'),
        'width' => 800,
        'height' => 800,
      ],
      'b' => [
        'label' => 'Large',
        'description' => $this->t('b: 1024px on longest side'),
        'width' => 1024,
        'height' => 1024,
      ],
      'h' => [
        'label' => 'Large 1600',
        'description' => $this->t('h: 1600px on longest side'),
        'width' => 1600,
        'height' => 1600,
      ],
      'k' => [
        'label' => 'Large 2048',
        'description' => $this->t('k: 2048px on longest side'),
        'width' => 2048,
        'height' => 2048,
      ],
      'o' => [
        'label' => 'Original',
        'description' => $this->t('o: Original image'),
        'width' => 2048,
        'height' => 2048,
      ],
      'x' => [
        'label' => 'slideshow',
        'description' => $this->t('x: Full featured responsive slideshow (for group, set and user IDs only)'),
      ],
      'y' => [
        'label' => 'Simple slideshow',
        'description' => $this->t('y: Basic responsive slideshow (for set and user IDs only)'),
      ],
    ];
  }

  /**
   * Returns TRUE if a value is found in a multidimensional array.
   *
   * See http://stackoverflow.com/a/4128377.
   *
   * @param string $needle
   *   The value to be matched.
   * @param array $haystack
   *   The array to match.
   * @param bool $strict
   *   If set to TRUE also check the types of the needle in the haystack.
   *
   * @return bool
   *   TRUE if match found.
   */
  public function inArrayR($needle, array $haystack, $strict = FALSE) {
    foreach ($haystack as $item) {
      if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && self::inArrayR($needle, $item, $strict))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Returns the URL to $photo.
   *
   * With size $size using the correct image farm
   * from the $photo variable.
   *
   * @param string $photo
   *   Photo to which the url should point.
   * @param string $size
   *   Size of the photo.
   * @param string $format
   *   Format of the photo.
   *
   * @return array
   *   URL for $photo with the correct size and format.
   */
  public function photoImgUrl($photo, $size = NULL, $format = NULL) {
    // Early images don't have a farm setting so default to 1.
    $farm = isset($photo['farm']) ? $photo['farm'] : 1;
    $server = $photo['server'];
    // photoset's use primary instead of id to specify the image.
    $id = isset($photo['primary']) ? $photo['primary'] : $photo['id'];
    $secret = $photo['secret'];
    $suffix = $size ? "_$size." : '.';
    $suffix = $size == '-' ? '.' : $suffix;
    $extension = $size == 'o' ? $format : 'jpg';

    return "https://farm{$farm}.static.flickr.com/{$server}/{$id}_{$secret}" . $suffix . $extension;
  }

}
