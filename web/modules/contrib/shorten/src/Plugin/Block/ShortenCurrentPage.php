<?php
/**
 * @file
 * Contains \Drupal\search\Plugin\Block\ShortenCurrentPage.
 */

namespace Drupal\shorten\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Shorten URL for Current Page' block.
 *
 * @Block(
 *   id = "shorten_short",
 *   admin_label = @Translation("Short URL"),
 *   category = @Translation("Forms")
 * )
 */
class ShortenCurrentPage extends BlockBase {

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
    // drupal_set_message(t('This block displays the short URL for the page on which it is shown, which can slow down uncached pages in some instances.'), 'warning');
    return \Drupal::formBuilder()->getForm('Drupal\shorten\Form\ShortenFormCurrentPage');
  }
}
