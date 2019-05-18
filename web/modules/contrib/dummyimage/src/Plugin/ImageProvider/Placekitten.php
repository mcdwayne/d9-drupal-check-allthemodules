<?php
/**
 * @file
 * Contains \Drupal\dummyimage\Plugin\ImageProvider\Placekitten
 */

namespace Drupal\dummyimage\Plugin\ImageProvider;

use Drupal\dummyimage\DummyImageProviderBase;

/**
 *
 * @ImageProvider(
 *   id = "placekitten",
 *   name = @Translation("Placekitten"),
 *   url = "http://placekitten.com"
 * )
 */
class Placekitten extends DummyImageProviderBase {

  public function getUrl($width, $height) {
    return 'http://placekitten.com/' . $width . '/' . $height;
  }
}
