<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Ubercart 6 attribute value source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\AttributeValue
 * @group commerce_migrate
 * @group commerce_migrate_uc
 */
class AttributeValueTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_ubercart'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data'] = [
      'uc_attributes' =>
        [
          [
            'aid' => '1',
            'name' => 'color',
            'label' => 'Color',
            'ordering' => '5',
            'required' => '1',
            'display' => '3',
            'description' => 'Color description',
          ],
          [
            'aid' => '5',
            'name' => 'size',
            'label' => 'Size',
            'ordering' => '1',
            'required' => '1',
            'display' => '2',
            'description' => 'Size description',
          ],
        ],
      'uc_attribute_options' =>
        [
          [
            'aid' => '1',
            'oid' => '1',
            'name' => 'Blue',
            'cost' => '2.00',
            'price' => '4.00',
            'weight' => '0',
            'ordering' => '5',
          ],
          [
            'aid' => '5',
            'oid' => '5',
            'name' => 'Small',
            'cost' => '5.00',
            'price' => '6.00',
            'weight' => '9',
            'ordering' => '1',
          ],
        ],
    ];
    $tests[0]['expected_data'] =
      [
        [
          'aid' => '1',
          'label' => 'Color',
          'ordering' => '5',
          'required' => '1',
          'display' => '3',
          'description' => 'Color description',
          'uco_aid' => '1',
          'oid' => '1',
          'cost' => '2.00',
          'price' => '4.00',
          'weight' => '0',
          'uco_ordering' => '5',
          'attribute_aid' => '1',
          'attribute_name' => 'color',
          'option_aid' => '1',
          'option_name' => 'Blue',
        ],
        [
          'aid' => '5',
          'label' => 'Size',
          'ordering' => '1',
          'required' => '1',
          'display' => '2',
          'description' => 'Size description',
          'uco_aid' => '5',
          'oid' => '5',
          'cost' => '5.00',
          'price' => '6.00',
          'weight' => '9',
          'uco_ordering' => '1',
          'attribute_aid' => '5',
          'attribute_name' => 'size',
          'option_aid' => '5',
          'option_name' => 'Small',
        ],
      ];

    return $tests;
  }

}
