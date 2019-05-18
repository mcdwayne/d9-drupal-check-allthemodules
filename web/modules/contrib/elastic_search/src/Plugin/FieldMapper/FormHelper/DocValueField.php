<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.02.17
 * Time: 15:36
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

/**
 * Class DocValueFormFieldTrait
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper
 */
/**
 * Class DocValueField
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper\FormHelper
 */
trait DocValueField {

  use AbstractTranslation;

  /**
   * @param bool $default
   *
   * @return array
   */
  protected function getDocValueField(bool $default) {
    return [
      $this->getDocValueFieldId() => [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Column-stride Storage'),
        '#description'   => $this->t('Should the field be stored on disk in a column-stride fashion, so that it can later be used for sorting, aggregations, or scripting? Accepts true (default) or false'),
        '#default_value' => $default,
      ],
    ];
  }

  /**
   * @return string
   */
  protected function getDocValueFieldId(): string {
    return 'doc_values';
  }

  /**
   * @return bool
   */
  protected function getDocValueFieldDefault(): bool {
    return TRUE;
  }

}