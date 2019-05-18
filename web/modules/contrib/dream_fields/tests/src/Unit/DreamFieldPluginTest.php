<?php

namespace Drupal\Tests\dream_fields\Unit;

use Drupal\dream_fields\FieldCreator;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\Tests\UnitTestCase;

/**
 * Test each dream field plugin.
 *
 * @group dream_fields
 */
class DreamFieldPluginTest extends UnitTestCase {

  /**
   * A data provider ::testPlugin.
   *
   * Currently only testing plugins that have some logic around field creation
   * that depends on the form values. Plugins with no complexity would simply
   * be duplicating information.
   */
  public function pluginTestCases() {
    return [
      'Date Plugin (with date)' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldDate',
        [
          'time_as_well' => TRUE,
        ],
        FieldCreator::createBuilder()
          ->setField('datetime', [
            'datetime_type' => 'datetime',
          ])
          ->setDisplay('datetime_default')
          ->setWidget('datetime_default'),
      ],
      'Date Plugin (without date)' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldDate',
        [
          'time_as_well' => FALSE,
        ],
        FieldCreator::createBuilder()
          ->setField('datetime', [
            'datetime_type' => 'date',
          ])
          ->setDisplay('datetime_default')
          ->setWidget('datetime_default'),
      ],
      'Image with configured image style' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldImage',
        [
          'image_style' => 'foo',
        ],
        FieldCreator::createBuilder()
          ->setField('image')
          ->setWidget('image_image')
          ->setDisplay('image', [
            'image_style' => 'foo',
            'image_link' => '',
          ], 'visually_hidden'),
      ],
      'Checkboxes with manual data' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldListCheckbox',
        [
          'list_type' => 'manual',
          'list_type_manual' => "Field A\nField B",
        ],
        FieldCreator::createBuilder()
          ->setCardinality(FieldStorageDefinition::CARDINALITY_UNLIMITED)
          ->setField('list_string', [
            'allowed_values' => [
              'Field A' => 'Field A',
              'Field B' => 'Field B'
            ],
          ])
          ->setWidget('options_buttons'),
      ],
      'Checkboxes with manual data (admin defined keys' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldListCheckbox',
        [
          'list_type' => 'manual',
          'list_type_manual' => "foo|Field A\nbar|Field B",
        ],
        FieldCreator::createBuilder()
          ->setCardinality(FieldStorageDefinition::CARDINALITY_UNLIMITED)
          ->setField('list_string', [
            'allowed_values' => [
              'foo' => 'Field A',
              'bar' => 'Field B'
            ],
          ])
          ->setWidget('options_buttons'),
      ],
      'List select with manual data' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldListSelect',
        [
          'list_type' => 'manual',
          'list_type_manual' => "Field A\nField B",
        ],
        FieldCreator::createBuilder()
          ->setCardinality(1)
          ->setField('list_string', [
            'allowed_values' => [
              'Field A' => 'Field A',
              'Field B' => 'Field B'
            ],
          ])
          ->setWidget('options_select'),
      ],
      'Radio with manual data' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldListRadio',
        [
          'list_type' => 'manual',
          'list_type_manual' => "Field A\nField B",
        ],
        FieldCreator::createBuilder()
          ->setCardinality(1)
          ->setField('list_string', [
            'allowed_values' => [
              'Field A' => 'Field A',
              'Field B' => 'Field B'
            ],
          ])
          ->setWidget('options_buttons'),
      ],
      'Checkbox Nodes' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldListCheckbox',
        [
          'list_type' => 'link_to_entity',
          'list_type_link_to_entity' => 'article',
        ],
        FieldCreator::createBuilder()
          ->setCardinality(FieldStorageDefinition::CARDINALITY_UNLIMITED)
          ->setField('entity_reference', [
            'target_type' => 'node',
          ], [
            'link_to_entity' => TRUE,
            'hanlder' => 'default:node',
            'hanlder_settings' => [
              'target_bundles' => [
                'article' => 'article'
              ],
            ],
          ])
          ->setWidget('options_buttons'),
      ],
      'Radios with existing vocabulary' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldListRadio',
        [
          'list_type' => 'link_to_voc',
          'list_type_link_to_voc' => 'foovocab',
        ],
        FieldCreator::createBuilder()
          ->setCardinality(1)
          ->setField('entity_reference', [
            'target_type' => 'taxonomy_term',
          ], [
            'link_to_entity' => TRUE,
            'handler' => 'default',
            'handler_settings' => [
              'target_bundles' => [
                'foovocab' => 'foovocab',
              ],
            ],
          ])
          ->setWidget('options_buttons'),
      ],
      'Number (float with commas)' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldNumber',
        [
          'number_type' => 'float',
          'thousand_separator' => ',',
          'decimal_separator' => ',',
        ],
        FieldCreator::createBuilder()
          ->setField('float')
          ->setDisplay('number_decimal', [
            'thousand_separator' => ',',
            'decimal_separator' => ',',
          ])
          ->setWidget('number'),
      ],
      'Number (decimal)' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldNumber',
        [
          'number_type' => 'decimal',
          'thousand_separator' => ',',
          'decimal_separator' => ',',
        ],
        FieldCreator::createBuilder()
          ->setField('decimal')
          ->setDisplay('number_decimal', [
            'thousand_separator' => ',',
            'decimal_separator' => ',',
          ])
          ->setWidget('number'),
      ],
      'Autocomplete Reference' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldReference',
        [
          'target_type' => 'node',
        ],
        FieldCreator::createBuilder()
          ->setField('entity_reference', [
            'target_type' => 'node',
          ], [
            'link_to_entity' => TRUE,
            'handler' => 'default',
            'handler_settings' => [
              'target_bundles' => [],
            ],
          ])
          ->setWidget('entity_reference_autocomplete')
      ],
      'Link (with title)' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldLink',
        [
          'collect_title' => TRUE,
        ],
        FieldCreator::createBuilder()
          ->setField('link', [], [
            'title' => 1,
          ])
          ->setWidget('link_default')
          ->setDisplay('link', [
            'trim_length' => NULL,
            'url_only' => FALSE,
            'url_plain' => FALSE,
            'rel' => '0',
            'target' => '0',
          ])
      ],
      'Link (no title)' => [
        '\Drupal\dream_fields\Plugin\DreamField\DreamFieldLink',
        [
          'collect_title' => FALSE,
        ],
        FieldCreator::createBuilder()
          ->setField('link', [], [
            'title' => 0,
          ])
          ->setWidget('link_default')
          ->setDisplay('link', [
            'trim_length' => NULL,
            'url_only' => FALSE,
            'url_plain' => FALSE,
            'rel' => '0',
            'target' => '0',
          ])
      ],
    ];
  }

  /**
   * Test the dream field plugins.
   *
   * @dataProvider pluginTestCases
   */
  public function testPlugin($plugin, $form_values, $expected_field_builder) {
    $field_builder = FieldCreator::createBuilder();
    $plugin = new $plugin([], '', []);
    $plugin->saveForm($form_values, $field_builder);
    $this->assertEquals($expected_field_builder, $field_builder);
  }

}
