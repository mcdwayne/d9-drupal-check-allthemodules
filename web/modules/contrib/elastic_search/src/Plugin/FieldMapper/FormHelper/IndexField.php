<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.02.17
 * Time: 15:13
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

/**
 * Class IndexField
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper\FormHelper
 */
trait IndexField {

  use AbstractTranslation;

  /**
   * @param bool $default
   *
   * @return array
   */
  protected function getIndexField(bool $default) {

    return [
      $this->getIndexFieldId() => [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Is Analyzed?'),
        '#description'   => $this->t('Should the field be searchable? Accepts true (default) and false'),
        '#default_value' => $default,
      ],
    ];

  }

  /**
   * @return string
   */
  protected function getIndexFieldId(): string {
    return 'index';
  }

  /**
   * @return bool
   */
  protected function getIndexFieldDefault(): bool {
    return TRUE;
  }
}