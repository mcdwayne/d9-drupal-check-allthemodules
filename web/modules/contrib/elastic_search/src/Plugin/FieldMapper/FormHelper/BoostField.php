<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.02.17
 * Time: 15:03
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

/**
 * Trait BoostFormFieldTrait
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper
 */
/**
 * Class BoostField
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper\FormHelper
 */
trait BoostField {

  use AbstractTranslation;

  /**
   * @param float $default
   *
   * @return array
   */
  protected function getBoostField($default): array {
    return [
      $this->getBoostFieldId() => [
        '#type'          => 'number',
        '#title'         => $this->t('Boost Value'),
        '#description'   => $this->t('A value equal or higher than 0 in the format 1.00'),
        '#default_value' => $default,
        '#min'           => 0,
        '#step'          => 0.01,
      ],
    ];
  }

  /**
   * @return string
   */
  protected function getBoostFieldId(): string {
    return 'boost';
  }

  /**
   * @return float
   */
  protected function getBoostFieldDefault(): float {
    return 0.0;
  }

}