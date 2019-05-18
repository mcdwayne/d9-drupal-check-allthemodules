<?php

namespace Drupal\Tests\prometheus_exporter\Unit\Plugin\MetricsCollector;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\prometheus_exporter\Plugin\MetricsCollector\UpdateStatusCollector;
use Drupal\Tests\UnitTestCase;
use Drupal\update\UpdateManagerInterface;

/**
 * @coversDefaultClass \Drupal\prometheus_exporter\Plugin\MetricsCollector\UpdateStatusCollector
 * @group prometheus_exporter
 */
class UpdateStatusCollectorTest extends UnitTestCase {

  /**
   * @covers ::collectMetrics
   */
  public function testCollectMetrics() {

    $updateManager = $this->prophesize(UpdateManagerInterface::class);
    $updateManager->getProjects()->willReturn($this->getTestProjectData());

    $moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $moduleHandler->moduleExists('update')->willReturn(TRUE);

    $collector = new UpdateStatusCollector(['description' => 'Dummy description.'], 'update_status_test', [], $updateManager->reveal(), $moduleHandler->reveal());
    $metrics = $collector->collectMetrics();

    $this->assertCount(3, $metrics);

    /** @var \PNX\Prometheus\Metric $core */
    $core = $metrics[0];
    $this->assertEquals('gauge', $core->getType());
    $this->assertEquals('drupal_update_status_test_core_version', $core->getName());
    $this->assertEquals('Drupal core version', $core->getHelp());
    $labelledValues = $core->getLabelledValues();
    $this->assertEquals(['version' => '8.6.1'], $labelledValues[0]->getLabels());

    /** @var \PNX\Prometheus\Metric $module */
    $module = $metrics[1];
    $this->assertEquals('gauge', $module->getType());
    $this->assertEquals('drupal_update_status_test_module_version', $module->getName());
    $this->assertEquals('Drupal module version', $module->getHelp());
    $labelledValues = $module->getLabelledValues();
    $this->assertEquals(['version' => '8.x-1.3', 'name' => 'test_module'], $labelledValues[0]->getLabels());

    /** @var \PNX\Prometheus\Metric $theme */
    $theme = $metrics[2];
    $this->assertEquals('gauge', $theme->getType());
    $this->assertEquals('drupal_update_status_test_theme_version', $theme->getName());
    $this->assertEquals('Drupal theme version', $theme->getHelp());
    $labelledValues = $theme->getLabelledValues();
    $this->assertEquals(['version' => '8.x-2.x', 'name' => 'test_theme'], $labelledValues[0]->getLabels());

  }

  /**
   * Provides test data for the update manager.
   *
   * @return array
   *   The test project data.
   */
  protected function getTestProjectData() {

    return [
      'test_core' => [
        'project_type' => 'core',
        'info' => [
          'version' => '8.6.1',
        ],
      ],
      'test_module' => [
        'project_type' => 'module',
        'info' => [
          'version' => '8.x-1.3',
        ],
      ],
      'test_theme' => [
        'project_type' => 'theme',
        'info' => [
          'version' => '8.x-2.x',
        ],
      ],
    ];
  }

}
