<?php

namespace Drupal\lightfoot;

use Drupal\Core\Asset\CssCollectionOptimizer;
use Drupal\Core\PrivateKey;

/**
 * Optimizes CSS assets.
 */
class LightfootCssCollectionOptimizer extends CssCollectionOptimizer {
  use LightfootRoundtripTrait;

  /**
   * {@inheritdoc}
   */
  public function optimize(array $css_assets) {
    //return parent::optimize($css_assets);

    header('LightfootCssCollectionOptimizer: optimize');

    // Group the assets.
    $css_groups = $this->grouper->group($css_assets);

    // Now get the URI for each asset group.
    $css_assets = [];
    foreach ($css_groups as $order => $css_group) {
      // We have to return a single asset, not a group of assets. It is now up
      // to one of the pieces of code in the switch statement below to set the
      // 'data' property to the appropriate value.
      $css_assets[$order] = $css_group;
      unset($css_assets[$order]['items']);

      switch ($css_group['type']) {
        case 'file':
          // No preprocessing, single CSS asset: just use the existing URI.
          if (!$css_group['preprocess']) {
            $uri = $css_group['items'][0]['data'];
            $css_assets[$order]['data'] = $uri;
          }
          else {
            $uri = self::generateUri($css_group, 'css', \Drupal::service('private_key'), $this->state)->toString();

            // Set the URI for this group's aggregate file.
            $css_assets[$order]['data'] = $uri;
            $css_assets[$order]['preprocessed'] = TRUE;
          }
          break;

        case 'external':
          // We don't do any aggregation and hence also no caching for external
          // CSS assets.
          $uri = $css_group['items'][0]['data'];
          $css_assets[$order]['data'] = $uri;
          break;
      }
    }

    return $css_assets;
  }
}

