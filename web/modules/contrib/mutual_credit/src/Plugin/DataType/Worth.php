<?php

namespace Drupal\mcapi\Plugin\DataType;

use Drupal\mcapi\Entity\Currency;
use Drupal\Core\TypedData\TypedData;

/**
 * A data type for the worth fieldtype
 *
 * @DataType(
 *   id = "mcapi_worth",
 *   label = @Translation("Worth")
 * )
 * 
 */
class Worth extends TypedData {

  /**
   * The id of the currency.
   *
   * @var string
   */
  protected $curr_id;

  /**
   * @var int
   */
  protected $value;


  /**
   * {@inheritdoc}
   */
  public function getString() {
    return Currency::load($this->curr_id)->format($this->value);
  }
}
