<?php

/**
 * @file
 * Contains \Drupal\simplecalculator\Plugin\Block\SimplecalculatorBlock.
 */

namespace Drupal\simple_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'simplecalculator' block.
 *
 * @Block(
 *   id = "simplecalculator_block",
 *   admin_label = @Translation("Simple Calculator"),
 *   category = @Translation("Simplecalculator")
 * )
 */
class SimpleCalculator extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $twig = \Drupal::service('twig');
    $template = $twig->loadTemplate(drupal_get_path('module', 'simple_calculator') . '/templates/simplecalculator.html.twig');
    $output = $template->render([]);
    return array(
      '#markup' => $output,
    );
  }

}
