<?php
/**
 * @file
 * Contains \Drupal\dummyimage\Plugin\ImageProvider\Flickholdr
 */

namespace Drupal\dummyimage\Plugin\ImageProvider;

use Drupal\dummyimage\DummyImageProviderBase;

/**
 *
 * @ImageProvider(
 *   id = "flickholdr",
 *   name = @Translation("Flickholdr"),
 *   url = "http://flickholdr.iwerk.org"
 * )
 */
class Flickholdr extends DummyImageProviderBase {

  public function getUrl($width, $height) {
    return 'http://flickholdr.iwerk.org/' . $width . '/' . $height;
  }
}