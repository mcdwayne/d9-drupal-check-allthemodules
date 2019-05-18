<?php
/**
 * @file
 * Contains \Drupal\dummyimage\Plugin\ImageProvider\Placepuppy
 */

namespace Drupal\dummyimage\Plugin\ImageProvider;

use Drupal\dummyimage\DummyImageProviderBase;

/**
 *
 * @ImageProvider(
 *   id = "placepuppy",
 *   name = @Translation("Placepuppy"),
 *   url = "http://placepuppy.it"
 * )
 */
class Placepuppy extends DummyImageProviderBase {

  public function getUrl($width, $height) {
    return 'http://placepuppy.it/' . $width . '/' . $height;
  }
}