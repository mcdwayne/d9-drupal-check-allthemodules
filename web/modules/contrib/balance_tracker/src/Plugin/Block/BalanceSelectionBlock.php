<?php

namespace Drupal\balance_tracker\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the user balance selection block.
 *
 * @Block(
 *   id = "balance_sheet_selection",
 *   admin_label = @Translation("User Balance Sheet selection block")
 * )
 */
class BalanceSelectionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['balance_tracker:user:' . \Drupal::currentUser()->id()],
      ],
      'form' => \Drupal::formBuilder()->getForm('\Drupal\balance_tracker\Form\BalanceTrackerUserSheetForm'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'view all balances');
  }

}
