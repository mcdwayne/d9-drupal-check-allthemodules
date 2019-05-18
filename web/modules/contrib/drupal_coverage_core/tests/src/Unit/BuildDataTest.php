<?php

namespace Drupal\Tests\drupal_coverage_core\Unit;

use Drupal\drupal_coverage_core\BuildData;
use Drupal\drupal_coverage_core\Exception\InvalidModuleTypeException;
use Drupal\drupal_coverage_core\Generator;
use Drupal\drupal_coverage_core\ModuleManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\drupal_coverage_core\BuildData
 * @group drupal_coverage_core
 */
class BuildDataTest extends UnitTestCase  {

  /**
   * @var \Drupal\drupal_coverage_core\BuildData
   */
  protected $buildData;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->buildData = new BuildData();
  }

  /**
   * Tests the setModuleType() method.
   *
   * @dataProvider moduleTypeProvider()
   */
  public function testSetModuleType($actual, $expected) {
    if ($expected !== FALSE) {
      $this->buildData->setModuleType($actual);
      $this->assertEquals($expected, $this->buildData->getModuleType());
    }
    else {
      $this->setExpectedException(InvalidModuleTypeException::class);
      $this->buildData->setModuleType($actual);
    }
  }

  /**
   * Provides test data for testSetModuleType.
   *
   * @return array
   *   The test data.
   */
  public function moduleTypeProvider() {
    return [
      // Provides a Contributed module type.
      [ModuleManager::TYPE_CONTRIB, ModuleManager::TYPE_CONTRIB],
      // Provides a Core module type.
      [ModuleManager::TYPE_CORE, ModuleManager::TYPE_CORE],
      // Provides a non-existing module type.
      [$this->getRandomGenerator()->string(), FALSE]
    ];
  }

  /**
   * Tests the getBuildStatus() method.
   *
   * @dataProvider buildStatusProvider()
   */
  public function testGetBuildStatus($class, $expected) {
    $this->buildData->setBuildData($class);
    $this->assertEquals($expected, $this->buildData->getBuildStatus());
  }

  /**
   * Provides test data for testGetBuildStatus.
   *
   * @return array
   *   The test data.
   */
  public function buildStatusProvider() {
    $build_data_1 = new \stdClass();
    $build_data_2 = new \stdClass();
    $build_data_1->state = "failed";
    $build_data_2->state = "finished";

    return [
      "No build data" => [NULL, Generator::BUILD_BUILDING],
      "Build data which has been failed" => [$build_data_1, Generator::BUILD_FAILED],
      "Build data which has been finished" => [$build_data_2, Generator::BUILD_SUCCESSFUL],
    ];
  }

}
