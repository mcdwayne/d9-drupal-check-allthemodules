<?php

/**
 * @file
 * Contains \Drupal\noughts_and_crosses\Plugin\Block\BoardBlock.
 */

namespace Drupal\noughts_and_crosses\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
/**
 * Provides a 'noughts_and_crosses' block.
 *
 * @Block(
 *   id = "noughts_and_crosses_block",
 *   admin_label = @Translation("Play Noughts And Crosses"),
 *   category = @Translation("Noughts And Crosses")
 * )
 */
class BoardBlock extends BlockBase {

  /**
   * The form class name.
   *
   * @var \Drupal\noughts_and_crosses\Form\Board\BoardStepOneForm
   */
  private $form_class = '\Drupal\noughts_and_crosses\Form\Board\BoardStepOneForm';

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [    
      'form' => \Drupal::formBuilder()->getForm($this->form_class),
    ];
    return $build;
   }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    $current_route_name = \Drupal::routeMatch()->getRouteName();
    $route_array = [
      'noughts_and_crosses.board',
      'noughts_and_crosses.board_step_one',
      'noughts_and_crosses.board_step_two',
    ];

    if (in_array($current_route_name, $route_array)) {
      return AccessResult::neutral();
    } else {
      return AccessResult::allowed();
    }
  }

}
