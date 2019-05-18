<?php

namespace Drupal\Tests\commerce_migrate\Kernel\Plugin\migrate\process;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\commerce_migrate\Plugin\migrate\process\CommerceAdjustments;
use Drupal\commerce_order\Adjustment;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;

/**
 * Tests the CommerceAdjustment plugin.
 *
 * @coversDefaultClass \Drupal\commerce_migrate\Plugin\migrate\process\CommerceAdjustments
 *
 * @group commerce_migrate
 */
class CommerceAdjustmentsTest extends CommerceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_order_test',
  ];

  /**
   * The CommerceAdjustment plugin.
   *
   * @var \Drupal\migrate\Plugin\MigrateProcessInterface
   */
  protected $plugin;

  /**
   * The migrate row.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * MigrateExecutable for the test.
   *
   * @var \Drupal\migrate\MigrateExecutable|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $migrateExecutable;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->row = new Row([]);
    $configuration = [];
    $this->plugin = new CommerceAdjustments($configuration, 'map', []);
    $this->migrateExecutable = $this->getMockBuilder(MigrateExecutable::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests valid inputs to CommerceAdjustments.
   *
   * @dataProvider providerValidCommerceAdjustments
   */
  public function testValidCommerceAdjustments($value = NULL) {
    $adjustments = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');
    foreach ($adjustments as $adjustment) {
      $this->assertInstanceOf(Adjustment::class, $adjustment);
    }
  }

  /**
   * Data provider for testValidCommerceAdjustments.
   *
   * @dataProvider providerValidCommerceAdjustments
   */
  public function providerValidCommerceAdjustments() {

    $tests = [
      // Single adjustment.
      [
        [
          [
            'type' => 'custom',
            'title' => '10% off',
            'amount' => '1.23',
            'currency_code' => 'CAD',
          ],
        ],
      ],
      // An untrimmed source amount.
      [
        [
          [
            'type' => 'custom',
            'title' => '10% off',
            'amount' => '1.23000',
            'currency_code' => 'CAD',
          ],
        ],
      ],
      // Multiple adjustments.
      [
        [
          [
            'type' => 'custom',
            'title' => '10% off',
            'amount' => '1.23',
            'currency_code' => 'CAD',
          ],
          [
            'type' => 'custom',
            'title' => '$ off',
            'amount' => '20.00',
            'currency_code' => 'CAD',
          ],
        ],
      ],
      // Empty type field.
      [
        [
          [
            'type' => '',
            'title' => 'No type',
            'amount' => '1.00',
            'currency_code' => 'CAD',
          ],
        ],
      ],
      // Empty title field with no label.
      [
        [
          [
            'type' => 'custom',
            'title' => '',
            'label' => 'Empty title',
            'amount' => '2.00',
            'currency_code' => 'CAD',
          ],
        ],
      ],
      // Empty label field with no title.
      [
        [
          [
            'type' => 'custom',
            'title' => 'Empty label',
            'label' => '',
            'amount' => '2.00',
            'currency_code' => 'CAD',
          ],
        ],
      ],
    ];
    return $tests;
  }

  /**
   * Tests invalid inputs to CommerceAdjustments.
   *
   * @dataProvider providerNoCommerceAdjustment
   */
  public function testNoCommerceAdjustments($value = NULL, $expected = NULL) {
    $new_value = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertEquals($expected, $new_value);
  }

  /**
   * Data provider for providerInvalidCommerceAdjustments.
   */
  public function providerNoCommerceAdjustment() {
    $tests =
      [
        // An string input.
        [
          'not an array',
          'not an array',
        ],
        [
          // An empty array.
          [],
          [],
        ],
      ];
    return $tests;
  }

  /**
   * Tests Commerce Price exceptions.
   *
   * @dataProvider providerExceptionPrice
   */
  public function testExceptionPrice($value = NULL) {
    $msg = sprintf('Failed creating price for adjustment %s', var_export($value, TRUE));
    $this->setExpectedException(MigrateSkipRowException::class, $msg);
    $this->plugin->transform([$value], $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Data provider for testExceptionPrice.
   */
  public function providerExceptionPrice() {
    $tests =
      [
        [
          // Empty currency_code field.
          [
            'type' => 'custom',
            'title' => 'test',
            'amount' => '1.00',
            'currency_code' => '',
          ],
        ],
        [
          // Futuristic currency_code.
          [
            'type' => 'custom',
            'title' => 'test',
            'amount' => '2.00',
            'currency_code' => 'Latinum',
          ],
        ],
        [
          // Numeric currency_code.
          [
            'type' => 'custom',
            'title' => 'test',
            'amount' => '3.00',
            'currency_code' => '1234',
          ],
        ],
        [
          // Numeric currency_code.
          [
            'type' => 'custom',
            'title' => 'test',
            'amount' => 'string',
            'currency_code' => 'CAD',
          ],
        ],

      ];
    return $tests;
  }

  /**
   * Tests required values not set.
   *
   * @dataProvider providerNotSet
   */
  public function testNotSet($value = NULL, $property = NULL) {
    if (is_string($property)) {
      $msg = sprintf("Property '%s' is not set for adjustment '%s'", $property, var_export($value, TRUE));
    }
    else {
      $msg = sprintf("Properties 'amount' and 'currency_code' are not set for adjustment '%s'", var_export($value, TRUE));
    }
    $this->setExpectedException(MigrateSkipRowException::class, $msg);
    $this->plugin->transform([$value], $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Data provider for testNotSet.
   */
  public function providerNotSet() {
    $tests =
      [
        [
          [
            'type' => 'custom',
            'title' => 'test',
            'amount' => '4.00',
          ],
          'currency_code',
        ],
        [
          [
            'type' => 'custom',
            'title' => 'test',
            'currency_code' => 'CAD',
          ],
          'amount',
        ],
        [
          [
            'type' => 'custom',
            'title' => 'test',
          ],
          ['amount', 'currency_code'],
        ],
      ];
    return $tests;
  }

}
