<?php

namespace Drupal\Tests\migrate_process_extras\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate_process_extras\Plugin\migrate\process\DrupalDate;

/**
 * Test the drupal_date process plugin.
 *
 * @group migrate_process_extras
 */
class DrupalDateTest extends KernelTestBase {

  use ProcessMocksTrait {
    setUp as mockSetUp;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['datetime'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mockSetUp();
  }

  /**
   * Test the transformations.
   *
   * @dataProvider dateFormatDataProvider
   */
  public function testTransform($configuration, $input, $expected) {
    $plugin = new DrupalDate($configuration, 'drupal_date', []);
    $this->assertEquals($expected, $plugin->transform($input, $this->migrateExecutable, $this->row, 'destinationproperty'));
  }

  /**
   * Date format test data provider.
   */
  public function dateFormatDataProvider() {
    return [
      'Date only storage format' => [
        ['storage_format' => 'date', 'format' => 'd-m-Y'],
        '10-05-2017', '2017-05-10',
      ],
      'Datetime storage format' => [
        ['storage_format' => 'datetime', 'format' => 'd-m-Y H:i:s'],
        '10-05-2017 14:30:10', '2017-05-10T14:30:10',
      ],
    ];
  }

}
