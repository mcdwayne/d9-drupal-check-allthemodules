<?php

namespace Drupal\closeblock\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'ClearCookie' Block.
 *
 * @Block(
 *   id = "closeblock_clear_cookie_block",
 *   admin_label = @Translation("Clear Cookie"),
 *   category = @Translation("Close block"),
 * )
 */
class ClearCookieBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\closeblock\Form\CloseBlockClearCookieForm');

    $build['closeblock_block'] = [
      '#theme' => 'custom_block_closeblock',
      '#content'  => $form,
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, \Drupal::currentUser()->hasPermission('close block'));
  }

}
