<?php

namespace Drupal\pexels\Plugin\stock_photo_search\Provider;

use Drupal\stock_photo_search\Annotation\StockPhotoProvider;
use Drupal\stock_photo_search\ProviderPluginBase;
use Drupal\pexels\PexelsAPI;

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
  public function getRemoteImageUrl($input) {
    $this->httpClient->head($input);

    return $input;
  }

  /**
   * {@inheritdoc}
   */
  public function searchFromApi($query = "", $page = 1) {
    $api = new PexelsAPI();
    $results = $api->search($this->getSearchValue());

    return $results;
  }

}
