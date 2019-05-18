<?php

namespace Drupal\elastic_search\Plugin\ElasticAbstractField;

/**
 * Class ReverseGeo
 *
 * @package Drupal\elastic_search\Plugin\ElasticAbstractField
 *
 * @ElasticAbstractField(
 *   id = "reverse_geo",
 *   label = @Translation("Reverse Geo"),
 *   description = @Translation("Reversegeofield Abstract Field plugin"),
 *   field_types = {
 *     "reverse_geo"
 *   }
 * )
 */
class ReverseGeo extends ElasticAbstractFieldBase {

  /**
   * {@inheritdoc}
   */
  public function getAbstractFields() {
    return [
      'address1'       => [
        'map' =>
          [
            0 =>
              [
                'type'    => 'text',
                'options' => [],
              ],
          ],
        'nested'   => '',
      ],
      'address2' => [
        'map'    => [
          0 => [
            'type'    => 'text',
            'options' => [],
          ],
        ],
        'nested' => '',
      ],
      'city'     => [
        'map'    => [
          0 => [
            'type'    => 'text',
            'options' => [],
          ],
        ],
        'nested' => '',
      ],
      'state'    => [
        'map'    => [
          0 => [
            'type'    => 'text',
            'options' => [],
          ],
        ],
        'nested' => '',
      ],
      'postcode' => [
        'map'    => [
          0 => [
            'type'    => 'text',
            'options' => [],
          ],
        ],
        'nested' => '',
      ],
      'lat'      => [
        'map'    => [
          0 => [
            'type'    => 'text',
            'options' => [],
          ],
        ],
        'nested' => '',
      ],
      'lng'      => [
        'map'    => [
          0 => [
            'type'    => 'text',
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
