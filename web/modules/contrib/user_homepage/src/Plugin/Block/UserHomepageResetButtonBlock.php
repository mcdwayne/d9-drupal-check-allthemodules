<?php

namespace Drupal\user_homepage\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Reset homepage button' block.
 *
 * @Block(
 *   id = "user_homepage_reset_button",
 *   admin_label = @Translation("User Homepage - Reset homepage button"),
 *   category = @Translation("Forms")
 * )
 */
class UserHomepageResetButtonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if (Drupal::currentUser()->hasPermission('configure own homepage')) {
      $userHomepageManager = Drupal::service('user_homepage.manager');
      $userHomepage = $userHomepageManager->getUserHomepage(Drupal::currentUser()->id());

      if (!empty($userHomepage)) {
        $formBuilder = Drupal::formBuilder();
        $form = $formBuilder->getForm('Drupal\user_homepage\Form\UserHomepageResetButtonForm');
        $build['form'] = $form;
      }
    }
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
