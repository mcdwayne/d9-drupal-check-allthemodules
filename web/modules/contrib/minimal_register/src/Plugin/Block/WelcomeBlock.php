<?php

namespace Drupal\minimal_register\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Welcome' Block.
 *
 * @Block(
 *   id = "welcome_block",
 *   admin_label = @Translation("Welcome Block"),
 *   category = @Translation("Blocks"),
 * )
 */
class WelcomeBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\minimal_register\Form\WelcomeForm');
    return $form;
  }

  public function blockForm($form, FormStateInterface $form_state)
  {
    return parent::blockForm($form, $form_state);
  }
}