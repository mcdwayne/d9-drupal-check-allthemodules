<?php

namespace Drupal\Tests\drupal_coverage_core\Unit;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\drupal_coverage_core\AnalysisManager;
use Drupal\drupal_coverage_core\BuildData;
use Drupal\drupal_coverage_core\Client\TravisClient;
use Drupal\drupal_coverage_core\Generator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\drupal_coverage_core\BuildData
 *
 * @group drupal_coverage_core
 */
class AnalysisManagerTest extends UnitTestCase {

  /**
   * The analysis manager this will be tested.
   *
   * @var AnalysisManager
   */
  protected $analysisManager;

  /**
   * The mockup of the DateFormatter.
   *
   * @var DateFormatter|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->dateFormatter = $this->getMock(DateFormatterInterface::class);
    $travis_client = new TravisClient();
    $generator = new Generator($travis_client);
    $this->analysisManager = new AnalysisManager($this->dateFormatter, $travis_client, $generator);
  }

  /**
   * Tests the updateBuildStatus() method.
   *
   * @dataProvider dataUpdateBuildStatus()
   */
  public function testUpdateBuildStatus($state, $result, $expected) {
    $build_data = new BuildData();
    $travis_data = new \stdClass();
    $travis_data->result = $result;

    if (!is_null($state)) {
      $travis_data->state = $state;
    }

    $build_data->setBuildData($travis_data);
    $build_status = $this->analysisManager->updateBuildStatus($build_data);

    $this->assertEquals($expected, $build_status);
  }

  /**
   * Data provider for testUpdateBuildStatus().
   */
  public function dataUpdateBuildStatus() {
    return [
      [NULL, NULL, Generator::BUILD_BUILDING],
      [NULL, 0, Generator::BUILD_BUILDING],
      ["failed", NULL, Generator::BUILD_BUILDING],
      ["failed", 0, Generator::BUILD_BUILDING],
      ["finished", NULL, Generator::BUILD_FAILED],
      ["finished", 0, Generator::BUILD_SUCCESSFUL],
    ];
  }

}
