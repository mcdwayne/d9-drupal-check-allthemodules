<?php

namespace Drupal\Tests\prometheus_exporter\Unit\Plugin\MetricsCollector;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\prometheus_exporter\Plugin\MetricsCollector\QueueSizeCollector;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\prometheus_exporter\Plugin\MetricsCollector\QueueSizeCollector
 * @group prometheus_exporter
 */
class QueueSizeCollectorTest extends UnitTestCase {

  /**
   * @covers ::collectMetrics
   */
  public function testCollectMetrics() {

    $queue = $this->prophesize(QueueInterface::class);
    $queue->numberOfItems()->willReturn(42);

    $queueFactory = $this->prophesize(QueueFactory::class);
    $queueFactory->get('test_queue')->willReturn($queue->reveal());

    $config = [
      'settings' => [
        'queues' => [
          'test_queue',
        ],
      ],
    ];
    $definition = [
      'provider' => 'test',
      'description' => 'Dummy description',
    ];
    $collector = new QueueSizeCollector($config, 'queue_size_test', $definition, $queueFactory->reveal());

    $metrics = $collector->collectMetrics();

    $this->assertCount(1, $metrics);

    /** @var \PNX\Prometheus\Metric $metric */
    $metric = $metrics[0];

    $this->assertEquals('gauge', $metric->getType());
    $this->assertEquals('drupal_queue_size_test_total', $metric->getName());
    $this->assertEquals('Dummy description', $metric->getHelp());
    $labelledValues = $metric->getLabelledValues();
    $labelledValue = $labelledValues[0];
    $this->assertEquals(['queue' => 'test_queue'], $labelledValue->getLabels());
    $this->assertEquals(42, $labelledValue->getValue());

  }

}
