<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Ubercart 6 attribute source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\Attribute
 * @group commerce_migrate
 * @group commerce_migrate_uc
 */
class AttributeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_ubercart'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data']['uc_attributes'] =
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
      ];
    $tests[0]['expected_data'] = $tests[0]['source_data']['uc_attributes'];
    return $tests;
  }

}
