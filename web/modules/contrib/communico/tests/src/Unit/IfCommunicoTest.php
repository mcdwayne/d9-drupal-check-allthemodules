<?php

/**
 * @file
 * Contains \Drupal\Tests\communico\IfCommunicoTest.
 */

namespace Drupal\Tests\communico\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Basic Communico test.
 *
 * @group test_example
 */
class IfCommunicoTest extends UnitTestCase {

  /**
   * Connector to test.
   *
   * @var \Drupal\communico\ConnectorService
   */
  public $communicoService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->communicoService = new \Drupal\communico\ConnectorService();
  }

  /**
   * A simple test that tests our getFeed function.
   */
  public function testGetFeed() {
    $communico_config = \Drupal::config('communico.settings');
    if ($config['communico_block_start'] == NULL || $config['communico_block_start'] == '') {
      $config['communico_block_start'] = date('Y-m-d');
    }

    if ($config['communico_block_end'] == NULL || $config['communico_block_end'] == '') {
      $current_date = date('Y-m-d');
      $config['communico_block_end'] = date('Y-m-d', strtotime($current_date . "+7 days"));
    }

    $this->assertEquals(32, $this->conversionService->getFeed($config['communico_block_start'], $config['communico_block_end'], $config['communico_block_type'], $config['communico_block_limit']));
  }

}
