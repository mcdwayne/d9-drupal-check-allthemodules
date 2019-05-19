<?php

/**
 * @file
 * Contains Drupal\unix_time_conversion\Plugin\Block\DatetToUnixTimestampBlock.
 */

namespace Drupal\unix_time_conversion\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'date to unix timestamp' block.
 *
 * @Block(
 *   id = "date_to_unix_timestamp",
 *   admin_label = @Translation("Date To Unix Timestamp"),
 * )
 */
class DateToUnixTimestampBlock extends BlockBase {

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
    return \Drupal::formBuilder()->getForm('Drupal\unix_time_conversion\Form\DateToUnixTimestampBlockForm');
  }

}
