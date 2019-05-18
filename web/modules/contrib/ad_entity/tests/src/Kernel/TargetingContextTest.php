<?php

namespace Drupal\Tests\ad_entity\Unit;

use Drupal\ad_entity\Plugin\ad_entity\AdContext\TargetingContext;
use Drupal\ad_entity\TargetingCollection;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the TargetingContext class.
 *
 * @coversDefaultClass \Drupal\ad_entity\Plugin\ad_entity\AdContext\TargetingContext
 * @group ad_entity
 */
class TargetingContextTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'ad_entity',
    'ad_entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['ad_entity']);
  }

  /**
   * Test the Json encoding results to expect.
   *
   * @dataProvider jsonEncodeExpected
   */
  public function testJsonEncode($info, $expected_result) {
    $context_data = [
      'context_id' => 'targeting',
      'settings' => ['targeting' => $info],
      'apply_on' => [],
    ];
    $encoded = TargetingContext::getJsonEncode($context_data);
    $this->assertContains($expected_result, $encoded);

    // Collection encoding must have an equal result.
    $collection = new TargetingCollection($info);
    $collection->filter();
    $this->assertEquals($expected_result, $collection->toJson());
  }

  /**
   * Data provider for ::testJsonEncode().
   *
   * @return array
   *   The data used for testing expected encoding results.
   */
  public function jsonEncodeExpected() {
    return [
      [['testkey' => 'testval'], '{"testkey":"testval"}'],
      [['<h1>testkey</h1>' => ' <script>testval "</script>'], '{"testkey":"testval \u0022"}'],
    ];
  }

}
