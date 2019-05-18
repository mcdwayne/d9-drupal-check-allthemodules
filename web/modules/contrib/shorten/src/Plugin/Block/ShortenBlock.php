<?php
/**
 * @file
 * Contains \Drupal\search\Plugin\Block\ShortenBlock.
 */

namespace Drupal\shorten\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Shorten URL' block.
 *
 * @Block(
 *   id = "shorten",
 *   admin_label = @Translation("Shorten URLs"),
 *   category = @Translation("Forms")
 * )
 */
class ShortenBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  // protected function blockAccess(AccountInterface $account) {
  //   return $account->hasPermission('use Shorten URLs page');
  // }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\shorten\Form\ShortenShortenForm');
  }
}
