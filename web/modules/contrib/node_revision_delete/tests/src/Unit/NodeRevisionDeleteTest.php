<?php

namespace Drupal\Tests\node_revision_delete\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\node_revision_delete\NodeRevisionDelete;
use Drupal\Tests\node_revision_delete\Traits\NodeRevisionDeleteTestTrait;

/**
 * Tests the NodeRevisionDelete class methods.
 *
 * @group node_revision_delete
 * @coversDefaultClass \Drupal\node_revision_delete\NodeRevisionDelete
 */
class NodeRevisionDeleteTest extends UnitTestCase {

  use NodeRevisionDeleteTestTrait;

  /**
   * A connection instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $connection;

  /**
   * A config factory instance.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The NodeRevisionDelete Object.
   *
   * @var Drupal\node_revision_delete\NodeRevisionDelete
   */
  protected $nodeRevisionDelete;

  /**
   * The configuration file name.
   *
   * @var string
   */
  protected $configFile;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Setting the config file.
    $this->configFile = 'node_revision_delete.settings';

    // Connection mock.
    $this->connection = $this->createMock('Drupal\Core\Database\Connection');
    // Config factory mock.
    $this->configFactory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');

    // Creating the object.
    $this->nodeRevisionDelete = new NodeRevisionDelete($this->configFactory, $this->getStringTranslationStub(), $this->connection);
  }

  /**
   * Tests the updateTimeMaxNumberConfig() method.
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param string $node_revision_delete_track
   *   Node revision delete track array.
   * @param string $config_name
   *   Config name to update (when_to_delete or minimum_age_to_delete).
   * @param int $max_number
   *   The maximum number for $config_name parameter.
   *
   * @covers ::updateTimeMaxNumberConfig
   * @dataProvider providerUpdateTimeMaxNumberConfig
   */
  public function testUpdateTimeMaxNumberConfig($expected, $node_revision_delete_track, $config_name, $max_number) {
    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('node_revision_delete_track')
      ->willReturn($node_revision_delete_track);

    // ImmutableConfig::set mock.
    $config->expects($this->any())
      ->method('set')
      ->with('node_revision_delete_track', $this->anything())
      ->willReturnSelf();

    // ImmutableConfig::save mock.
    $config->expects($this->any())
      ->method('save')
      ->willReturnSelf();

    // Mocking getEditable method.
    $this->configFactory->expects($this->any())
      ->method('getEditable')
      ->with($this->configFile)
      ->willReturn($config);

    // Testing the function.
    $this->assertArrayEquals($expected, $this->nodeRevisionDelete->updateTimeMaxNumberConfig($config_name, $max_number));
  }

  /**
   * Data provider for testUpdateTimeMaxNumberConfig.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from updateTimeMaxNumberConfig().
   *   - 'track' - Node revision delete track array.
   *   - 'config_name' - Config name to update.
   *   - 'max_number' - The maximum number for $config_name parameter.
   *
   * @see testUpdateTimeMaxNumberConfig()
   */
  public function providerUpdateTimeMaxNumberConfig() {

    $expected = [
      // Test 1.
      [
        'article' => [
          'minimum_revisions_to_keep' => 20,
          'minimum_age_to_delete' => 5,
          'when_to_delete' => 12,
        ],
        'blog' => [
          'minimum_revisions_to_keep' => 5,
          'minimum_age_to_delete' => 3,
          'when_to_delete' => 10,
        ],
        'page' => [
          'minimum_revisions_to_keep' => 4,
          'minimum_age_to_delete' => 5,
          'when_to_delete' => 8,
        ],
      ],
      // Test 2.
      [
        'article' => [
          'minimum_revisions_to_keep' => 20,
          'minimum_age_to_delete' => 8,
          'when_to_delete' => 10,
        ],
        'blog' => [
          'minimum_revisions_to_keep' => 5,
          'minimum_age_to_delete' => 3,
          'when_to_delete' => 10,
        ],
        'page' => [
          'minimum_revisions_to_keep' => 4,
          'minimum_age_to_delete' => 6,
          'when_to_delete' => 8,
        ],
      ],
      // Test 3.
      [
        'article' => [
          'minimum_revisions_to_keep' => 20,
          'minimum_age_to_delete' => 3,
          'when_to_delete' => 12,
        ],
        'blog' => [
          'minimum_revisions_to_keep' => 5,
          'minimum_age_to_delete' => 3,
          'when_to_delete' => 10,
        ],
        'page' => [
          'minimum_revisions_to_keep' => 4,
          'minimum_age_to_delete' => 3,
          'when_to_delete' => 8,
        ],
      ],
      // Test 4.
      [
        'article' => [
          'minimum_revisions_to_keep' => 20,
          'minimum_age_to_delete' => 8,
          'when_to_delete' => 8,
        ],
        'blog' => [
          'minimum_revisions_to_keep' => 5,
          'minimum_age_to_delete' => 3,
          'when_to_delete' => 8,
        ],
        'page' => [
          'minimum_revisions_to_keep' => 4,
          'minimum_age_to_delete' => 6,
          'when_to_delete' => 8,
        ],
      ],
      // Test 5.
      [
        'article' => [
          'minimum_revisions_to_keep' => 20,
          'minimum_age_to_delete' => 1,
          'when_to_delete' => 12,
        ],
        'blog' => [
          'minimum_revisions_to_keep' => 5,
          'minimum_age_to_delete' => 1,
          'when_to_delete' => 10,
        ],
        'page' => [
          'minimum_revisions_to_keep' => 4,
          'minimum_age_to_delete' => 1,
          'when_to_delete' => 8,
        ],
      ],
      // Test 6.
      [
        'article' => [
          'minimum_revisions_to_keep' => 20,
          'minimum_age_to_delete' => 8,
          'when_to_delete' => 4,
        ],
        'blog' => [
          'minimum_revisions_to_keep' => 5,
          'minimum_age_to_delete' => 3,
          'when_to_delete' => 4,
        ],
        'page' => [
          'minimum_revisions_to_keep' => 4,
          'minimum_age_to_delete' => 6,
          'when_to_delete' => 4,
        ],
      ],
    ];

    // Getting the content types to track.
    $track = $this->getNodeRevisionDeleteTrackArray();

    $tests['set 1'] = [$expected[0], $track, 'minimum_age_to_delete', 5];
    $tests['set 2'] = [$expected[1], $track, 'when_to_delete', 10];
    $tests['set 3'] = [$expected[2], $track, 'minimum_age_to_delete', 3];
    $tests['set 4'] = [$expected[3], $track, 'when_to_delete', 8];
    $tests['set 5'] = [$expected[4], $track, 'minimum_age_to_delete', 1];
    $tests['set 6'] = [$expected[5], $track, 'when_to_delete', 4];

    return $tests;
  }

  /**
   * Tests the getTimeString() method.
   *
   * @param string $expected
   *   The expected result from calling the function.
   * @param array $config_name_time
   *   The configured time name.
   * @param string $config_name
   *   The config name.
   * @param int $number
   *   The number for the $config_name parameter configuration.
   *
   * @covers ::getTimeString
   * @dataProvider providerGetTimeString
   */
  public function testGetTimeString($expected, array $config_name_time, $config_name, $number) {
    // ImmutableConfig mock.
    $config = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    // ImmutableConfig::get mock.
    $config->expects($this->any())
      ->method('get')
      ->with('node_revision_delete_' . $config_name . '_time')
      ->willReturn($config_name_time);

    // Mocking getEditable method.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with($this->configFile)
      ->willReturn($config);

    // Asserting the values.
    $this->assertEquals($expected, $this->nodeRevisionDelete->getTimeString($config_name, $number));
  }

  /**
   * Data provider for testGetTimeString.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getTimeString().
   *   - 'config_name_time' - The configured time name.
   *   - 'config_name' - The config name.
   *   - 'max_number' - The number for the $config_name parameter configuration.
   *
   * @see testUpdateTimeMaxNumberConfig()
   */
  public function providerGetTimeString() {

    $expected = [
      '5 days',
      '2 days',
      '1 day',
      '10 weeks',
      '20 weeks',
      '1 week',
      '12 months',
      '24 months',
      '1 month',
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 5, '@time' => 'days']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 2, '@time' => 'days']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 1, '@time' => 'day']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 10, '@time' => 'weeks']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 20, '@time' => 'weeks']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 1, '@time' => 'week']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 12, '@time' => 'months']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 24, '@time' => 'months']),
      $this->getStringTranslationStub()->translate('After @number @time of inactivity', ['@number' => 1, '@time' => 'month']),
    ];

    $days = ['time' => 'days'];
    $weeks = ['time' => 'weeks'];
    $months = ['time' => 'months'];

    // Test for minimum_age_to_delete.
    $tests['days 1'] = [$expected[0], $days, 'minimum_age_to_delete', 5];
    $tests['days 2'] = [$expected[1], $days, 'minimum_age_to_delete', 2];
    $tests['days 3'] = [$expected[2], $days, 'minimum_age_to_delete', 1];
    $tests['weeks 1'] = [$expected[3], $weeks, 'minimum_age_to_delete', 10];
    $tests['weeks 2'] = [$expected[4], $weeks, 'minimum_age_to_delete', 20];
    $tests['weeks 3'] = [$expected[5], $weeks, 'minimum_age_to_delete', 1];
    $tests['months 1'] = [$expected[6], $months, 'minimum_age_to_delete', 12];
    $tests['months 2'] = [$expected[7], $months, 'minimum_age_to_delete', 24];
    $tests['months 3'] = [$expected[8], $months, 'minimum_age_to_delete', 1];
    // Test for when_to_delete.
    $tests['days 4'] = [$expected[9], $days, 'when_to_delete', 5];
    $tests['days 5'] = [$expected[10], $days, 'when_to_delete', 2];
    $tests['days 6'] = [$expected[11], $days, 'when_to_delete', 1];
    $tests['weeks 4'] = [$expected[12], $weeks, 'when_to_delete', 10];
    $tests['weeks 5'] = [$expected[13], $weeks, 'when_to_delete', 20];
    $tests['weeks 6'] = [$expected[14], $weeks, 'when_to_delete', 1];
    $tests['months 4'] = [$expected[15], $months, 'when_to_delete', 12];
    $tests['months 5'] = [$expected[16], $months, 'when_to_delete', 24];
    $tests['months 6'] = [$expected[17], $months, 'when_to_delete', 1];

    return $tests;
  }

  /**
   * Tests the getTimeValues() method.
   *
   * @param int $expected
   *   The expected result from calling the function.
   * @param string $index
   *   The index to retrieve.
   *
   * @covers ::getTimeValues
   * @dataProvider providerGetTimeValues
   */
  public function testGetTimeValues($expected, $index) {
    // Testing the function.
    $this->assertEquals($expected, $this->nodeRevisionDelete->getTimeValues($index));
  }

  /**
   * Data provider for testGetTimeNumberString().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getTimeValues().
   *   - 'index' - The number.
   *
   * @see testGetTimeValues()
   */
  public function providerGetTimeValues() {
    $all_values = [
      '-1'       => 'Never',
      '0'        => 'Every time cron runs',
      '3600'     => 'Every hour',
      '86400'    => 'Everyday',
      '604800'   => 'Every week',
      '864000'   => 'Every 10 days',
      '1296000'  => 'Every 15 days',
      '2592000'  => 'Every month',
      '7776000'  => 'Every 3 months',
      '15552000' => 'Every 6 months',
      '31536000' => 'Every year',
      '63072000' => 'Every 2 years',
    ];

    $tests[] = [$all_values, NULL];
    $tests[] = [$all_values[-1], -1];
    $tests[] = [$all_values[0], 0];
    $tests[] = [$all_values[3600], 3600];
    $tests[] = [$all_values[86400], 86400];
    $tests[] = [$all_values[604800], 604800];
    $tests[] = [$all_values[864000], 864000];
    $tests[] = [$all_values[1296000], 1296000];
    $tests[] = [$all_values[2592000], 2592000];
    $tests[] = [$all_values[7776000], 7776000];
    $tests[] = [$all_values[15552000], 15552000];
    $tests[] = [$all_values[31536000], 31536000];
    $tests[] = [$all_values[63072000], 63072000];

    return $tests;
  }

  /**
   * Tests the getTimeNumberString() method.
   *
   * @param int $expected
   *   The expected result from calling the function.
   * @param string $number
   *   The number.
   * @param string $time
   *   The time option (days, weeks or months).
   *
   * @covers ::getTimeNumberString
   * @dataProvider providerGetTimeNumberString
   */
  public function testGetTimeNumberString($expected, $number, $time) {
    // Testing the function.
    $this->assertEquals($expected, $this->nodeRevisionDelete->getTimeNumberString($number, $time));
  }

  /**
   * Data provider for testGetTimeNumberString().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'expected' - Expected return from getTimeNumberString().
   *   - 'number' - The number.
   *   - 'time' - The time option (days, weeks or months).
   *
   * @see testGetTimeNumberString()
   */
  public function providerGetTimeNumberString() {
    // Days.
    $tests['day singular'] = ['day', 1, 'days'];
    $tests['day plural 1'] = ['days', 2, 'days'];
    $tests['day plural 2'] = ['days', 10, 'days'];
    // Weeks.
    $tests['week singular'] = ['week', 1, 'weeks'];
    $tests['week plural 1'] = ['weeks', 2, 'weeks'];
    $tests['week plural 2'] = ['weeks', 10, 'weeks'];
    // Months.
    $tests['month singular'] = ['month', 1, 'months'];
    $tests['month plural 1'] = ['months', 2, 'months'];
    $tests['month plural 2'] = ['months', 10, 'months'];

    return $tests;
  }

}
