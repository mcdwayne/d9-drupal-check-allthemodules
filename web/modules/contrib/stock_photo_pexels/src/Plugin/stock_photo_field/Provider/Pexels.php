<?php

namespace Drupal\stock_photo_pexels\Plugin\stock_photo_field\Provider;

use Drupal\stock_photo_field\Annotation\StockPhotoProvider;
use Drupal\stock_photo_field\ProviderPluginBase;

/**
 * A Pexels provider plugin.
 *
 * @StockPhotoProvider(
 *   id = "pexels",
 *   title = @Translation("Pexels")
 * )
 */
class Pexels extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getRemoteImageUrl() {
    $url = 'https://images.pexels.com/photos/%1$s/pexels-photo-%1$s.jpeg';
    $high_resolution = sprintf($url, $this->getImageId());
    $this->httpClient->head($high_resolution);

    return $high_resolution;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/(www\.|images\.)?pexels\.com\/photos?.*-(?<id>[0-9]*)\/?/', $input, $matches);

    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
