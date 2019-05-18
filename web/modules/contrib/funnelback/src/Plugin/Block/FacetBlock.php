<?php

namespace Drupal\funnelback\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block for funnelback facets.
 *
 * @Block(
 *   id = "funnelback_facet_block",
 *   admin_label = @Translation("Funnelback facets"),
 *   category = @Translation("Funnelback")
 * )
 */
class FacetBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $results = Funnelback::funnelbackStaticResultsCache();
    $output = [];
    if (Funnelback::funnelbackResultValidator($results)) {
      $facets = $results['facets'];
      $query = $results['summary']['query'];

      // Only support single dropdown, checkbox and radio button at the moment.
      Funnelback::funnelbackFilterFacetDisplay($facets);

      $output = [
        '#theme' => 'funnelback_facets_block',
        '#facets' => $facets,
        '#query' => $query,
        "#attached" => [
          'css' => [
            [
              'data' => drupal_get_path('module', 'funnelback') . '/css/funnelback.facet.css',
              'type' => 'file',
            ],
          ],
          'js' => [
            [
              'data' => drupal_get_path('module', 'funnelback') . '/js/funnelback.facet.js',
              'type' => 'file',
            ],
          ],
        ],
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'use funnelback search');
  }

}
