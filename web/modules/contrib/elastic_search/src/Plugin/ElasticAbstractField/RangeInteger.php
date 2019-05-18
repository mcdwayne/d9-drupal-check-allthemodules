<?php

namespace Drupal\elastic_search\Plugin\ElasticAbstractField;

/**
 * Class RangeInteger
 *
 * @package Drupal\elastic_search\Plugin\ElasticAbstractField
 *
 * @ElasticAbstractField(
 *   id = "range_integer",
 *   label = @Translation("Range Integer"),
 *   description = @Translation("Range Integer Abstract Field plugin"),
 *   field_types = {
 *     "range_integer"
 *   }
 * )
 */
class RangeInteger extends ElasticAbstractFieldBase {

  /**
   * {@inheritdoc}
   */
  public function getAbstractFields() {
    return [
      'to'   => [
        'map'    =>
          [
            0 =>
              [
                'type'    => 'integer',
                'options' => [],
              ],
          ],
        'nested' => '',
      ],
      'from' => [
        'map'    => [
          0 => [
            'type'    => 'integer',
            'options' => [],
          ],
        ],
        'nested' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isNested() {
    return TRUE;
  }

}
