<?php
/**
 * @file
 * Contains \Drupal\shurly\Plugin\Block\ShurlyBlock.
 */

namespace Drupal\shurly\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a block for creating short urls.
 * @Block(
 *   id = "shurl_create_form",
 *   admin_label = @Translation("Short URL form"),
 *   category = @Translation("Forms")
 * )
 */
class ShurlyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    
    if ($account->hasPermission('create short URLs') && $path_args[0] != 'shurly'){
      return True;
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\shurly\Form\ShurlyCreateForm');

    return $form;
  }
}
