<?php

namespace Drupal\lightfoot;

use Drupal\Core\Asset\JsCollectionOptimizer;
use Drupal\Core\PrivateKey;


/**
 * Optimizes JavaScript assets.
 */
class LightfootJsCollectionOptimizer extends JsCollectionOptimizer {
  use LightfootRoundtripTrait;

  /**
   * {@inheritdoc}
   */
  public function optimize(array $js_assets) {
    // Group the assets.
    $js_groups = $this->grouper->group($js_assets);

    // Now get the URI for each asset group.
    $js_assets = [];
    foreach ($js_groups as $order => $js_group) {
      // We have to return a single asset, not a group of assets. It is now up
      // to one of the pieces of code in the switch statement below to set the
      // 'data' property to the appropriate value.
      $js_assets[$order] = $js_group;
      unset($js_assets[$order]['items']);

      switch ($js_group['type']) {
        case 'file':
          // No preprocessing, single JS asset: just use the existing URI.
          if (!$js_group['preprocess']) {
            $uri = $js_group['items'][0]['data'];
            $js_assets[$order]['data'] = $uri;
          }
          // Preprocess (aggregate), unless the aggregate file already exists.
          else {
            $uri = self::generateUri($js_group, 'js', \Drupal::service('private_key'), $this->state)->toString();

            // Set the URI for this group's aggregate file.
            $js_assets[$order]['data'] = $uri;

            $js_assets[$order]['preprocessed'] = TRUE;
          }
          break;

        case 'external':
          // We don't do any aggregation and hence also no caching for external
          // JS assets.
          $uri = $js_group['items'][0]['data'];
          $js_assets[$order]['data'] = $uri;
          break;
      }
    }

    return $js_assets;
  }
}
