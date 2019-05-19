<?php

namespace Drupal\pixabay\Plugin\stock_photo_search\Provider;

use Drupal\stock_photo_search\Annotation\StockPhotoProvider;
use Drupal\stock_photo_search\ProviderPluginBase;
use Drupal\pixabay\PixabayAPI;

/**
 * A pixabay provider plugin.
 *
 * @StockPhotoProvider(
 *   id = "pixabay",
 *   title = @Translation("Pixabay")
 * )
 */
class Pixabay extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getRemoteImageUrl($input) {
    $this->httpClient->head($input);

    return $input;
  }

  /**
   * {@inheritdoc}
   */
  public function searchFromApi($query = "", $page = 1) {
    $api = new PixabayAPI();
    $results = $api->search($this->getSearchValue());

    return $results;
  }

}
