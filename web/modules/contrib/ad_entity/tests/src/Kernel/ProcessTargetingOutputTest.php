<?php

namespace Drupal\Tests\ad_entity\Kernel;

use Drupal\ad_entity\TargetingCollection;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the output processing filter on targeting information.
 *
 * @group ad_entity
 */
class ProcessTargetingOutputTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'ad_entity',
    'ad_entity_test',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['ad_entity', 'filter', 'ad_entity_test']);
  }

  /**
   * Test the processed output for expected results.
   *
   * @dataProvider expectedFilterResults
   */
  public function testProcessedOutput($format_id, $key, $value, $expected_key, $expected_value) {
    $config = $this->container->get('config.factory')->getEditable('ad_entity.settings');
    $config->set('process_targeting_output', $format_id);
    $config->save();

    $collection = new TargetingCollection();
    $collection->add($key, $value);
    $collection->filter();
    $this->assertEquals($expected_value, $collection->get($expected_key));
  }

  /**
   * Data provider for ::testProcessedOutput().
   *
   * @return array
   *   The data for testing at ::testProcessedOutput().
   */
  public function expectedFilterResults() {
    $key = 'testkey';
    $value = '<script>alert("Hi there.");</script>';
    return [
      [NULL, $key, $value, $key, trim(Xss::filter(strip_tags((($value)))))],
      ['ad_entity_plain', $key, $value, $key, Html::escape($value)],
      ['ad_entity_full', $key, $value, $key, $value],
    ];
  }

}
