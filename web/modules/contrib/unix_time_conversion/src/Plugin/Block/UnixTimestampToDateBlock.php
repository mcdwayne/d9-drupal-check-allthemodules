<?php

/**
 * @file
 * Contains Drupal\unix_time_conversion\Plugin\Block\UnixTimestampToDateBlock.
 */

namespace Drupal\unix_time_conversion\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'unix timestamp to date' block.
 *
 * @Block(
 *   id = "unix_timestamp_to_date_block",
 *   admin_label = @Translation("Unix Timestamp To Date"),
 * )
 */
class UnixTimestampToDateBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\unix_time_conversion\Form\UnixTimestampToDateBlockForm');
  }

}
