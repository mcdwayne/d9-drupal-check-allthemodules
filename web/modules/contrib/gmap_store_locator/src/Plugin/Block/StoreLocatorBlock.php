<?php

namespace Drupal\store_locator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\store_locator\Helper\LocationDataHelper;
use Drupal\store_locator\Helper\GoogleApiKeyHelper;

/**
 * Provides a 'Store Locator' block.
 *
 * @Block(
 * id = "store_locator",
 * admin_label = @Translation("Store Locator")
 * )
 */
class StoreLocatorBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = [];
    $content['map'] = ['#markup' => '<div id="map" class="loc-map block-map-view"></div>'];
    $location_data = LocationDataHelper::loadInfowindow('infowindow');
    $googleMapKey = GoogleApiKeyHelper::getGoogleApiKey();
    $content['#attached']['drupalSettings']['locator']['data'] = $location_data['itemlist'];
    $content['#attached']['drupalSettings']['locator']['markericon'] = $location_data['marker'];
    $content['#attached']['library'][] = 'store_locator/store_locator.page';
    $content['#attached']['html_head'][] = [$googleMapKey, 'googleMapKey'];

    return $content;
  }

}
