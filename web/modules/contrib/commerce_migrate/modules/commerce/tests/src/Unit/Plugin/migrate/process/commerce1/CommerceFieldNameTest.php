<?php

namespace Drupal\Tests\commerce_migrate_commerce\Unit\Plugin\migrate\process\commerce1;

use Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1\CommerceFieldName;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the CommerceFieldName plugin.
 *
 * @coversDefaultClass \Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1\CommerceFieldName
 *
 * @group commerce_migrate_commerce
 */
class CommerceFieldNameTest extends MigrateProcessTestCase {

  /**
   * Process plugin make_entity_unique.
   *
   * @var \Drupal\migrate\Plugin\migrate\process\MakeUniqueEntityField
   */
  protected $makeEntityUnique;

  /**
   * Process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManagerInterface
   */
  protected $processPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration_plugin_manager = $this->getMockBuilder('Drupal\migrate\Plugin\MigrationPluginManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->processPluginManager = $this->getMockBuilder('Drupal\migrate\Plugin\MigratePluginManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->makeEntityUnique = $this->getMockBuilder('Drupal\migrate\Plugin\migrate\process\MakeUniqueEntityField')
      ->disableOriginalConstructor()
      ->getMock();
    $this->migrateExecutable = $this->getMockBuilder('Drupal\migrate\MigrateExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->plugin = new CommerceFieldName([], 'test', [], $migration_plugin_manager, $this->processPluginManager);
  }

  /**
   * Tests CommerceFieldName plugin for an attribute.
   *
   * @dataProvider providerCommerceFieldName
   */
  public function testCommerceFieldName($destination_property = NULL, $source_properties = NULL, $expected = NULL) {
    $this->makeEntityUnique->expects($this->once())
      ->method('transform')
      ->willReturn('color');
    $this->processPluginManager->method('createInstance')
      ->willReturn($this->makeEntityUnique);

    $this->row->expects($this->any())
      ->method('getDestinationProperty')
      ->willReturn($destination_property);

    $this->row
      ->method('getSourceProperty')
      ->will($this->onConsecutiveCalls($source_properties[0], $source_properties[1], $source_properties[2], $source_properties[3]));

    $value = $this->plugin->transform('', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected, $value);
  }

  /**
   * Data provider for testCommerceFieldName().
   */
  public function providerCommerceFieldName() {
    // Tests attribute field name is stripped of leading 'field_'.
    $tests[0]['destination_property'] = NULL;

    $field_name = 'field_color';
    $entity_type = 'commerce_product';
    $type = 'taxonomy_term_reference';
    $instances = [
      [
        'data' => serialize([
          'widget' => [
            'type' => 'options_select',
          ],
        ]),
      ],
      [
        'data' => serialize([
          'widget' => [
            'type' => 'text',
          ],
        ]),
      ],
    ];
    $tests[0]['source_properties'] = [
      $field_name,
      $entity_type,
      $type,
      $instances,
    ];
    $tests[0]['expected'] = 'color';

    return $tests;
  }

  /**
   * Tests CommerceFieldName plugin for address field input.
   *
   * @dataProvider providerCommerceFieldNameAddress
   */
  public function testCommerceFieldNameAddress($source_properties = NULL, $expected = NULL) {
    $this->makeEntityUnique->expects($this->never())
      ->method('transform');

    $this->row->expects($this->never())
      ->method('getDestinationProperty');

    // Put the input values onto the Row.
    $this->row
      ->method('getSourceProperty')
      ->will($this->onConsecutiveCalls($source_properties[0], $source_properties[1], $source_properties[2], $source_properties[3]));

    $value = $this->plugin->transform('', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected, $value);
  }

  /**
   * Data provider for testCommerceFieldNameAddress().
   */
  public function providerCommerceFieldNameAddress() {
    $tests = [];
    // Tests address field name is changed.
    $field_name = 'color';
    $entity_type = 'commerce_customer_profile';
    $type = 'addressfield';
    $instances = [
      [
        'data' => serialize([
          'widget' => [
            'type' => 'options_select',
          ],
        ]),
      ],
      [
        'data' => serialize([
          'widget' => [
            'type' => 'text',
          ],
        ]),
      ],
    ];
    $tests[0]['source_properties'] = [
      $field_name,
      $entity_type,
      $type,
      $instances,
    ];
    $tests[0]['expected'] = 'address';

    // Tests address field name not on commerce_customer_profile is not changed.
    $field_name = 'field_address';
    $entity_type = 'node';
    $type = 'addressfield';
    $instances = [
      [
        'data' => serialize([
          'widget' => [
            'type' => 'options_select',
          ],
        ]),
      ],
      [
        'data' => serialize([
          'widget' => [
            'type' => 'text',
          ],
        ]),
      ],
    ];
    $tests[1]['source_properties'] = [
      $field_name,
      $entity_type,
      $type,
      $instances,
    ];
    $tests[1]['expected'] = 'field_address';

    // Tests the field name for field that is not an address is not changed.
    $field_name = 'field_text';
    $entity_type = 'node';
    $type = 'text';
    $instances = [
      [
        'data' => serialize([
          'widget' => [
            'type' => 'options_select',
          ],
        ]),
      ],
      [
        'data' => serialize([
          'widget' => [
            'type' => 'text',
          ],
        ]),
      ],
    ];
    $tests[1]['source_properties'] = [
      $field_name,
      $entity_type,
      $type,
      $instances,
    ];
    $tests[1]['expected'] = 'field_text';
    return $tests;
  }

  /**
   * Tests CommerceFieldName plugin for other entity types.
   *
   * @dataProvider providerCommerceFieldNameOther
   */
  public function testCommerceFieldNameOther($source_properties = NULL, $expected = NULL) {
    $this->makeEntityUnique->expects($this->never())
      ->method('transform');

    $this->row->expects($this->never())
      ->method('getDestinationProperty');

    $this->row
      ->method('getSourceProperty')
      ->will($this->onConsecutiveCalls($source_properties[0], $source_properties[1], $source_properties[2], $source_properties[3]));

    $value = $this->plugin->transform('', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected, $value);
  }

  /**
   * Data provider for testCommerceFieldNameOther().
   */
  public function providerCommerceFieldNameOther() {
    $tests = [];
    // Tests address field name is changed.
    $field_name = 'field_image';
    $entity_type = 'node';
    $type = 'text';
    $instances = [
      [
        'data' => serialize([
          'widget' => [
            'type' => 'options_select',
          ],
        ]),
      ],
      [
        'data' => serialize([
          'widget' => [
            'type' => 'text',
          ],
        ]),
      ],
    ];
    $tests[0]['source_properties'] = [
      $field_name,
      $entity_type,
      $type,
      $instances,
    ];
    $tests[0]['expected'] = 'field_image';

    return $tests;
  }

}
