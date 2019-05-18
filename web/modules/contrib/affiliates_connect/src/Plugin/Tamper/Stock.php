<?php

namespace Drupal\affiliates_connect\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the stock plugin.
 *
 * @Tamper(
 *   id = "stock",
 *   label = @Translation("Stock"),
 *   description = @Translation("Returns 1 if in stock else 0"),
 *   category = "Affiliates Connect"
 * )
 */
class Stock extends TamperBase {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      return 1;
    }
    if (strpos(strtolower($data), 'in stock') !== false) {
      return 1;
    }
    return 0;

  }
}
