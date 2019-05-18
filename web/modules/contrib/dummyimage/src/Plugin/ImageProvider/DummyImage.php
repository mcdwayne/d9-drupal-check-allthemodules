<?php
/**
 * @file
 * Contains \Drupal\dummyimage\Plugin\ImageProvider\DummyImage
 */

namespace Drupal\dummyimage\Plugin\ImageProvider;

use Drupal\dummyimage\DummyImageProviderBase;

/**
 *
 * @ImageProvider(
 *   id = "dummyimage",
 *   name = @Translation("Dummy Image"),
 *   url = "http://dummyimage.com"
 * )
 */
class DummyImage extends DummyImageProviderBase {

  public function getUrl($width, $height) {
    return 'http://dummyimage.com/' . $width . 'x' . $height;
  }
}