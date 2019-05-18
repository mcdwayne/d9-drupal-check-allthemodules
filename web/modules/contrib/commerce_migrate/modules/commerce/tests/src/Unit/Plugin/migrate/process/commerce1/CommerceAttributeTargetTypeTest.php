<?php

namespace Drupal\Tests\commerce_migrate_commerce\Unit\Plugin\migrate\process\commerce1;

use Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1\CommerceAttributeTargetType;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the CommerceAttributeTargetType plugin.
 *
 * @coversDefaultClass \Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1\CommerceAttributeTargetType
 *
 * @group commerce_migrate_commerce
 */
class CommerceAttributeTargetTypeTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new CommerceAttributeTargetType([], 'test', []);
  }

  /**
   * Tests CommerceAttributeTargetType.
   *
   * @dataProvider providerTestCommerceAttributeTargetType
   */
  public function testCommerceAttributeTargetType($src = NULL, $dst = NULL, $expected = NULL) {
    $this->row->expects($this->once())
      ->method('getDestinationProperty')
      ->willReturn($dst);

    $this->row
      ->method('getSourceProperty')
      ->will($this->onConsecutiveCalls($src[0], $src[1], $src[2]));

    $configuration = [];
    $value = $this->plugin->transform('', $this->migrateExecutable, $this->row, $dst);
    $this->assertSame($expected, $value);
  }

  /**
   * Data provider for testCommerceAttributeTargetType().
   */
  public function providerTestCommerceAttributeTargetType() {
    // Tests when this is an attribute value.
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
    ];
    $tests[0]['source_properties'] = [$entity_type, $type, $instances];
    $tests[0]['destination_property'] = 'original_target_type';
    $tests[0]['expected'] = 'commerce_product_attribute_value';

    // Tests when not an attribute value.
    $entity_type = 'commerce_product';
    $type = 'taxonomy_term_reference';
    $instances = [
      [
        'data' => serialize([
          'widget' => [
            'type' => 'text',
          ],
        ]),
      ],
    ];
    $tests[1]['source_properties'] = [$entity_type, $type, $instances];
    $tests[1]['destination_property'] = 'original_target_type';
    $tests[1]['expected'] = 'original_target_type';

    return $tests;
  }

  /**
   * Tests CommerceAttributeTargetType throws an exception.
   *
   * @dataProvider providerTestException
   */
  public function testException($src = NULL, $dst = NULL, $expected = NULL) {
    $this->row->expects($this->once())
      ->method('getDestinationProperty')
      ->willReturn($dst);

    $this->row
      ->method('getSourceProperty')
      ->will($this->onConsecutiveCalls($src[0], $src[1], $src[2]));

    $this->setExpectedException(MigrateSkipRowException::class, $expected);
    $this->plugin->transform('', $this->migrateExecutable, $this->row, $dst);
  }

  /**
   * Data provider for testException().
   */
  public function providerTestException() {
    $tests = [];

    // Empty array of instances.
    $tests[0]['source_properties'] = [
      'commerce_product',
      'taxonomy_term_reference',
      [],
    ];
    $tests[0]['destination_property'] = 'original_target_type';
    $tests[0]['expected'] = "No instances for attribute for destination 'original_target_type'";

    // Instances is NULL.
    $tests[1] = $tests[0];
    $tests[1]['source_properties'] = [
      'commerce_product',
      'taxonomy_term_reference',
      NULL,
    ];
    $tests[1]['destination_property'] = 'original_target_type';
    $tests[1]['expected'] = "No instances for attribute for destination 'original_target_type'";

    return $tests;
  }

}
