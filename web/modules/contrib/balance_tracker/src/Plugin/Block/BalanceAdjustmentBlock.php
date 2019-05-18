<?php

namespace Drupal\balance_tracker\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the user balance adjustment block.
 *
 * @Block(
 *   id = "balance_adjustment",
 *   admin_label = @Translation("User Balance Adjustment block")
 * )
 */
class BalanceAdjustmentBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return  [
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['balance_tracker:user:' . \Drupal::currentUser()->id()],
      ],
      'form' => \Drupal::formBuilder()->getForm('\Drupal\balance_tracker\Form\BalanceTrackerAdjustmentForm'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'adjust user balances');
  }
  
}
