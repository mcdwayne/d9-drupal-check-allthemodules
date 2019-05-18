<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.02.17
 * Time: 16:36
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

/**
 * Class IncludeInAllField
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper\FormHelper
 */
trait IncludeInAllField {

  use AbstractTranslation;

  /**
   * @param bool $default
   *
   * @return array
   */
  protected function getIncludeInAllField(bool $default) {
    return [
      $this->getIncludeInAllFieldId() => [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Include in All'),
        '#description'   => $this->t('Whether or not the field value should be included in the _all field? Accepts true or false. Defaults to false if index is set to false, or if a parent object field sets include_in_all to false. Otherwise defaults to true.'),
        '#default_value' => $default,
      ],
    ];
  }

  /**
   * @return string
   */
  protected function getIncludeInAllFieldId(): string {
    return 'include_in_all';
  }

  /**
   * @return bool
   */
  protected function getIncludeInAllFieldDefault(): bool {
    return TRUE;
  }

}