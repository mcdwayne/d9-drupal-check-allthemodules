<?php
/**
 * @file
 * Contains \Drupal\simplecalculator\Plugin\Block\HomeLoancalculatorBlock.
 */
namespace Drupal\simple_calculator\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
/**
 * Provides a 'homeloancalculator' block.
 *
 * @Block(
 *   id = "home_loan_calculator_block",
 *   admin_label = @Translation("Home Loan Calculator"),
 *   category = @Translation("HomeLoancalculator")
 * )
 */
class HomeLoancalculator extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\simple_calculator\Form\HomeLoanForm');
    return $form;
   }
}