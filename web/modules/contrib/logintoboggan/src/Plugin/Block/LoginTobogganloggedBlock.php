<?php

/**
 * @file
 * Contains \Drupal\logintoboggan\Plugin\Block\LoginTobogganloggedBlock.
 */

namespace Drupal\logintoboggan\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'LoginToboggan logged' block.
 *
 * @Block(
 *   id = "logintoboggan_logged_in",
 *   admin_label = @Translation("LoginToboggan logged in block"),
 *   module = "logintoboggan"
 * )
 */

class LoginTobogganloggedBlock extends BlockBase {
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    $user = \Drupal::currentUser();
    $page = array(
      '#theme' => 'lt_loggedinblock',
      '#account' => $user,
    );
    return $page;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }
    else{
      return AccessResult::allowed();
    }
  }

}