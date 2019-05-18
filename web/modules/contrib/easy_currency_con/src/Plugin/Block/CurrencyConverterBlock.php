<?php

/**
 * @file
 * Contains Drupal\easy_currency_con\Plugin\Block\CurrencyConverterBlock.
 */

namespace Drupal\easy_currency_con\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Currency Converter' block.
 *
 * @Block(
 *   id = "easy_currency_con",
 *   admin_label = @Translation("Easy Currency Con"),
 * )
 */
class CurrencyConverterBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array();
    $build['#attached']['library'][] = 'easy_currency_con/easy_currency_con.currency_converter';
    $build['currency_converter_form'] = \Drupal::formBuilder()->getForm('Drupal\easy_currency_con\Form\CurrencyConverterForm');
    return $build;
  }

}
