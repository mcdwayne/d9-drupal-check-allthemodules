<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.02.17
 * Time: 16:08
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

/**
 * Class NullValueField
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper\FormHelper
 */
trait NullValueField {

  use AbstractTranslation;

  /**
   * @param mixed $default
   * @param array $constraints
   *
   * @return array
   */
  protected function getNullValueField($default,
                                       array $constraints = [
                                         'min'  => 0,
                                         'step' => 1,
                                       ]) {
    $nullValue = [
      $this->getNullValueFieldId() => [
        '#type'          => 'number',
        '#title'         => $this->t('Null Value'),
        '#description'   => $this->t('Accepts a numeric value of the same type as the field which is substituted for any explicit null values. Defaults to null, which means the field is treated as missing.'),
        '#default_value' => $default,
      ],
    ];
    !array_key_exists('min', $constraints) ?:
      $nullValue['#min'] = $constraints['min'];
    !array_key_exists('max', $constraints) ?:
      $nullValue['#max'] = $constraints['max'];
    !array_key_exists('step', $constraints) ?:
      $nullValue['#step'] = $constraints['step'];
    return $nullValue;
  }

  /**
   * @return string
   */
  protected function getNullValueFieldId(): string {
    return 'null_value';
  }

  /**
   * @return null
   */
  protected function getNullValueFieldDefault() {
    return NULL;
  }

}