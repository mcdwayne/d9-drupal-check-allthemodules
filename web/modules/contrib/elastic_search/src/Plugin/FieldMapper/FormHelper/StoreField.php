<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.02.17
 * Time: 16:01
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

/**
 * Class StoreField
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper\FormHelper
 */
trait StoreField {

  /**
   * @param mixed $default
   *
   * @return array
   */
  protected function getStoreField($default) {
    return [
      'store' => [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Store'),
        '#description'   => $this->t('Whether the field value should be stored and retrievable separately from the _source field. Accepts true or false (default).'),
        '#default_value' => $default,
      ],
    ];
  }

  /**
   * @return string
   */
  protected function getStoreFieldId(): string {
    return 'store';
  }

  /**
   * @return bool
   */
  protected function getStoreFieldDefault(): bool {
    return FALSE;
  }

}