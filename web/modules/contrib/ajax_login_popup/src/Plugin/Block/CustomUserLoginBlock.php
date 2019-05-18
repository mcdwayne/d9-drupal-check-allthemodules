<?php

namespace Drupal\ajax_login_popup\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;
/**
 * Provides a 'Ajax User Login Block' block.
 *
 * @Block(
 *   id = "custom_ajax_user_login_block",
 *   admin_label = @Translation("Custom Ajax User Login Block"),
 *   category = @Translation("Custom Ajax User Login Block")
 * )
 */
class CustomUserLoginBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
	if (\Drupal::currentUser()->isAnonymous()) {
    $form = \Drupal::formBuilder()->getForm('Drupal\ajax_login_popup\Form\CustomUserLoginForm');
	}
    return $form;
  }
}
