<?php

namespace Drupal\funnelback\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block for funnelback contextual navigation.
 *
 * @Block(
 *   id = "funnelback_contextual_navigation_block",
 *   admin_label = @Translation("Funnelback contextual navigation"),
 *   category = @Translation("Funnelback")
 * )
 */
class ContextualNavigationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $results = Funnelback::funnelbackStaticResultsCache();
    $output = [];
    if (Funnelback::funnelbackResultValidator($results)) {
      $contextualNav = $results['contextual_nav'];
      $summary = $results['summary'];
      $output = [
        '#theme' => 'funnelback_contextual_nav_block',
        '#summary' => $summary,
        '#contextual_nav' => $contextualNav,
        "#attached" => [
          'css' => [
            [
              'data' => drupal_get_path('module', 'funnelback') . '/css/funnelback.contextual.css',
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
