<?php

namespace Drupal\user_homepage\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Save as homepage button' block.
 *
 * @Block(
 *   id = "user_homepage_save_button",
 *   admin_label = @Translation("User Homepage - Save as homepage button"),
 *   category = @Translation("Forms")
 * )
 */
class UserHomepageSaveButtonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (Drupal::currentUser()->hasPermission('configure own homepage')) {
      $userHomepageManager = Drupal::service('user_homepage.manager');

      if ($userHomepageManager->buildHomepagePathFromCurrentRequest() === $userHomepageManager->getUserHomepage(Drupal::currentUser()->id())) {
        $build['current_homepage_text'] = [
          '#type' => 'processed_text',
          '#text' => $this->t('This is your homepage.'),
        ];
      }
      else {
        $formBuilder = Drupal::formBuilder();
        $form = $formBuilder->getForm('Drupal\user_homepage\Form\UserHomepageSaveButtonForm');
        $build['form'] = $form;
      }

      // Never cache this block, as contents can change for each user.
      $build['#cache']['max-age'] = 0;
      return $build;
    }
  }

}
