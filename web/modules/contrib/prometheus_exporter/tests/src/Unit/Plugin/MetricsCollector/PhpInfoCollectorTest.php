<?php

namespace Drupal\Tests\prometheus_exporter\Unit\Plugin\MetricsCollector;

use Drupal\prometheus_exporter\Plugin\MetricsCollector\PhpInfoCollector;
use Drupal\prometheus_exporter\Plugin\MetricsCollector\PhpVersion;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\prometheus_exporter\Plugin\MetricsCollector\PhpInfoCollector
 * @group prometheus_exporter
 */
class PhpInfoCollectorTest extends TestCase {

  /**
   * @covers ::collectMetrics
   */
  public function testCollectMetrics() {

    $phpVersion = $this->prophesize(PhpVersion::class);
    $phpVersion->getString()->willReturn('7.2.10');
    $phpVersion->getId()->willReturn(70210);
    $phpVersion->getMajor()->willReturn(7);
    $phpVersion->getMinor()->willReturn(2);
    $phpVersion->getPatch()->willReturn(10);

    $collector = new PhpInfoCollector(['description' => 'Dummy description.'], 'phpinfo', [], $phpVersion->reveal());
    $metrics = $collector->collectMetrics();

    $this->assertCount(1, $metrics);

    /** @var \PNX\Prometheus\Metric $metric */
    $metric = $metrics[0];
    $this->assertEquals('gauge', $metric->getType());
    $this->assertEquals('drupal_phpinfo_version', $metric->getName());
    $this->assertEquals('Provides the PHP version', $metric->getHelp());

    $labelledValues = $metric->getLabelledValues();
    $labelledValue = $labelledValues[0];
    $this->assertEquals(70210, $labelledValue->getValue());
    $this->assertEquals([
      'version' => '7.2.10',
      'major' => 7,
      'minor' => 2,
      'patch' => 10,
    ], $labelledValue->getLabels());
  }

}
