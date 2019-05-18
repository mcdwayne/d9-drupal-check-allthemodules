<?php

namespace Drupal\age_calculator\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'age_calculator' block.
 *
 * @Block(
 *   id = "age_calculator_block",
 *   admin_label = @Translation("Age Calculator"),
 * )
 */
class AgeBlock extends BlockBase {

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
    $form = \Drupal::formBuilder()->getForm('Drupal\age_calculator\Form\AddForm');

    return array(
      'add_this_page' => $form,
    );
  }

}
