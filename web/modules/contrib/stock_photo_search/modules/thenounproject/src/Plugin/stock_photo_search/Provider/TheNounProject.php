<?php

namespace Drupal\thenounproject\Plugin\stock_photo_search\Provider;

use Drupal\stock_photo_search\Annotation\StockPhotoProvider;
use Drupal\stock_photo_search\ProviderPluginBase;
use Drupal\thenounproject\TheNounProjectAPI;

/**
 * A The Noun Project provider plugin.
 *
 * @StockPhotoProvider(
 *   id = "thenounproject",
 *   title = @Translation("The Noun Project")
 * )
 */
class TheNounProject extends ProviderPluginBase {

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
    $api = new TheNounProjectAPI();
    $results = $api->search($this->getSearchValue());

    return $results;
  }

}
