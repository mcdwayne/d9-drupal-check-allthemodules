<?php

namespace Drupal\Tests\prometheus_exporter\Unit\Plugin\MetricsCollector;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\prometheus_exporter\Plugin\MetricsCollector\NodeCount;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\prometheus_exporter\Plugin\MetricsCollector\NodeCount
 * @group prometheus_exporter
 */
class NodeCountTest extends TestCase {

  /**
   * @covers ::collectMetrics
   */
  public function testCollectMetrics() {

    $countQuery = $this->prophesize(QueryInterface::class);
    $countQuery->execute()->willReturn(42);
    $nodeQuery = $this->prophesize(QueryInterface::class);
    $nodeQuery->count()->willReturn($countQuery);

    $definition = [
      'provider' => 'node_count',
      'description' => 'Test description',
    ];

    $collector = new NodeCount([], 'node_count', $definition, $nodeQuery->reveal());

    $metrics = $collector->collectMetrics();

    $this->assertCount(1, $metrics);
    /** @var \PNX\Prometheus\Metric $metric */
    $metric = $metrics[0];
    $this->assertEquals('gauge', $metric->getType());
    $this->assertEquals('drupal_node_count_total', $metric->getName());
    $this->assertEquals('Test description', $metric->getHelp());

    $labelledValues = $metric->getLabelledValues();
    $labelledValue = $labelledValues[0];
    $this->assertEquals(42, $labelledValue->getValue());
    $this->assertEquals([], $labelledValue->getLabels());
  }

}
