<?php

/**
 * @file
 * Contains \Drupal\forgot_password_block\Plugin\Block\ForgotPasswordBlock.
 */

namespace Drupal\forgot_password_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Forgot Password Block' block.
 *
 * @Block(
 *   id = "forgot_password_block",
 *   admin_label = @Translation("Forgot Password Block")
 * )
 */
class ForgotPasswordBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
  $myblock = \Drupal::formBuilder()->getForm('Drupal\user\Form\UserPasswordForm', $foo);
    return array(
      '#type' => 'markup',
      '#markup' => drupal_render($myblock),
    );
  }

}
