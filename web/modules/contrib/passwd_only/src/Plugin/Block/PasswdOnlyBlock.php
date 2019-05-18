<?php

namespace Drupal\passwd_only\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provide a password only login block.
 *
 * @Block(
 *   id = "passwd_only_block",
 *   admin_label = @Translation("Password only user login"),
 *   category = @Translation("Password only user login"),
 * )
 */
class PasswdOnlyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\passwd_only\Form\LoginForm');
  }

}
