<?php

namespace Drupal\Tests\commerce_migrate_commerce\Unit\Plugin\migrate\process\commerce1;

use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;
use Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1\CommerceAttributeHandlerSetting;
use Drupal\migrate\MigrateSkipProcessException;

/**
 * Tests the CommerceAttributeTargetType plugin.
 *
 * @coversDefaultClass \Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1\CommerceAttributeHandlerSetting
 *
 * @group commerce_migrate_commerce
 */
class CommerceAttributeHandlerSettingTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->plugin = new CommerceAttributeHandlerSetting([], 'test', []);
  }

  /**
   * Tests with valid values.
   *
   * @dataProvider providerTestCommerceAttributeTargetType
   */
  public function testCommerceAttributeTargetType($src = NULL, $dst = NULL, $expected = NULL) {
    $this->row->expects($this->once())
      ->method('getDestinationProperty')
      ->willReturn($dst);

    $this->row
      ->method('getSourceProperty')
      ->will($this->onConsecutiveCalls($src[0], $src[1], $src[2], $src[3]));

    $configuration = [];
    $this->plugin = new CommerceAttributeHandlerSetting($configuration, 'map', []);
    $value = $this->plugin->transform('', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected, $value);
  }

  /**
   * Data provider for testCommerceAttributeTargetType().
   */
  public function providerTestCommerceAttributeTargetType() {
    // Tests when this is an attribute value.
    $tests = [
      [
        [
          'commerce_product',
          'taxonomy_term_reference',
          ['type' => 'options_select'],
          'product',
        ],
        [
          'settings',
        ],
        [
          'target_bundles' => ['product'],
        ],
      ],
    ];

    return $tests;
  }

  /**
   * Tests invalid cases.
   *
   * @dataProvider providerTestException
   */
  public function testException($src = NULL) {
    $this->row
      ->method('getSourceProperty')
      ->will($this->onConsecutiveCalls($src[0], $src[1], $src[2], $src[3]));

    $this->setExpectedException(MigrateSkipProcessException::class);
    $this->plugin->transform([], $this->migrateExecutable, $this->row, 'property');
  }

  /**
   * Data provider for testException().
   */
  public function providerTestException() {
    // Tests when this is an attribute value.
    $tests = [];

    $tests = [
      // Not a commerce product entity type.
      [
        'node',
        'taxonomy_term_reference',
        ['type' => 'options_select'],
        [],
      ],
      // Not a taxonomy term reference type.
      [
        'commerce_product',
        'file',
        ['type' => 'options_select'],
        [],
      ],
      // Not an options select widget type.
      [
        'commerce_product',
        'taxonomy_term_reference',
        ['type' => 'text'],
        [],
      ],
      // Not a taxonomy term reference type.
      [
        NULL,
        NULL,
        NULL,
        NULL,
      ],
    ];

    return $tests;
  }

}
