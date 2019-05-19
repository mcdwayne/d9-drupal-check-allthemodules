<?php

namespace Drupal\giphy\Plugin\stock_photo_search\Provider;

use Drupal\stock_photo_search\Annotation\StockPhotoProvider;
use Drupal\stock_photo_search\ProviderPluginBase;
use Drupal\giphy\giphyAPI;

/**
 * A giphy provider plugin.
 *
 * @StockPhotoProvider(
 *   id = "giphy",
 *   title = @Translation("Giphy")
 * )
 */
class Giphy extends ProviderPluginBase {

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
    $api = new GiphyAPI();
    $results = $api->search($this->getSearchValue());

    return $results;
  }

}
