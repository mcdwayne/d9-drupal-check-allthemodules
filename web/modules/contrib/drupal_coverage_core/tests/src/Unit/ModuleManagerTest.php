<?php

namespace Drupal\Tests\drupal_coverage_core\Unit;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\drupal_coverage_core\AnalysisManager;
use Drupal\drupal_coverage_core\Client\TravisClient;
use Drupal\drupal_coverage_core\Generator;
use Drupal\drupal_coverage_core\ModuleManager;
use Drupal\drupal_coverage_core\ModuleManagerStorage;
use Drupal\drupal_coverage_core\ModuleManagerStorageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\drupal_coverage_core\ModuleManager
 * @group drupal_coverage_core
 */
class ModuleManagerTest extends UnitTestCase {

  /**
   * The tested module manager.
   *
   * @var ModuleManager
   */
  protected $moduleManager;

  /**
   * The mocked storage for the module manager.
   *
   * @var ModuleManagerStorage|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleManagerStorage;

  /**
   * The mocked analysis manager.
   *
   * @var AnalysisManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $analysisManager;

  /**
   * The mocked date formatter.
   *
   * @var DateFormatter|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $dateFormatter;

  /**
   * The client that will interact with TravisCI.
   *
   * @var TravisClient
   */
  protected $travisClient;

  /**
   * The generator used for generating build data.
   *
   * @var Generator
   */
  protected $generator;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->dateFormatter = $this->getMock(DateFormatterInterface::class);
    $this->travisClient = $this->getMock(TravisClient::class);
    $this->generator = $this->getMockBuilder(Generator::class)
      ->setConstructorArgs([$this->travisClient])
      ->getMock();
    $this->moduleManagerStorage = $this->getMock(ModuleManagerStorageInterface::class);
    $this->configFactory = $this->getConfigFactoryStub(array());
    $this->analysisManager = new AnalysisManager($this->dateFormatter, $this->travisClient, $this->generator);
    $this->moduleManager = new ModuleManager($this->analysisManager, $this->moduleManagerStorage, $this->configFactory);
  }

  /**
   * Tests the cleanModuleName() method.
   *
   * @dataProvider dataCleanModuleName()
   */
  public function testCleanModuleName($module_name, $seperator, $expected) {
    if ($seperator == NULL) {
      $this->assertEquals($expected, $this->moduleManager->cleanModuleName($module_name));
    }
    else {
      $this->assertEquals($expected, $this->moduleManager->cleanModuleName($module_name, $seperator));
    }
  }

  /**
   * Provides data for testCleanModuleName().
   *
   * @return array
   *   The test data.
   */
  public function dataCleanModuleName() {
    return [
      ["Test Module", NULL, "test-module"],
      ["Test Module", "_", "test_module"],
      ["Test-module", NULL, "test-module"],
    ];
  }

}
