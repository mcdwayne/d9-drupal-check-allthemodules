<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.02.17
 * Time: 16:38
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

/**
 * Class IgnoreMalformedField
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper\FormHelper
 */
trait IgnoreMalformedField {

  use AbstractTranslation;

  /**
   * @param bool $default
   *
   * @return array
   */
  protected function getIgnoreMalformedField(bool $default) {
    return [
      $this->getIgnoreMalformedFieldId() => [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Ignore Malformed'),
        '#description'   => $this->t('If true, malformed numbers are ignored. If false (default), malformed numbers throw an exception and reject the whole document.'),
        '#default_value' => $default,
      ],
    ];
  }

  /**
   * @return string
   */
  protected function getIgnoreMalformedFieldId(): string {
    return 'ignore_malformed';
  }

  /**
   * @return bool
   */
  protected function getIgnoreMalformedFieldDefault(): bool {
    return TRUE;
  }
}