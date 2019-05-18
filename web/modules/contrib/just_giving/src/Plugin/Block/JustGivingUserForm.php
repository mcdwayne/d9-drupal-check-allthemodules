<?php

namespace Drupal\just_giving\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'JustGivingUserForm' block.
 *
 * @Block(
 *  id = "just_giving_user_form",
 *  admin_label = @Translation("Just giving user form"),
 * )
 */
class JustGivingUserForm extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form = \Drupal::formBuilder()->getForm('Drupal\just_giving\Form\JustGivingUserForm');

    return $form;
  }

}
