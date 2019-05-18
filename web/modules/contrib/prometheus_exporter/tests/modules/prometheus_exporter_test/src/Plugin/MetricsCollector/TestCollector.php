<?php

namespace Drupal\prometheus_exporter_test\Plugin\MetricsCollector;

use Drupal\prometheus_exporter\Plugin\BaseMetricsCollector;
use PNX\Prometheus\Gauge;

/**
 * Dummy plugin for testing purposes.
 *
 * @MetricsCollector(
 *   id = "test",
 *   title = @Translation("Test Collector"),
 *   description = @Translation("A test collector.")
 * )
 */
class TestCollector extends BaseMetricsCollector {

  /**
   * {@inheritdoc}
   */
  public function collectMetrics() {
    $metrics = [];

    $gauge = new Gauge($this->getNamespace(), 'total', $this->getDescription());
    $gauge->set(1234, ["foo" => "bar"]);
    $gauge->set(5678, ["baz" => "wiz"]);

    $metrics[] = $gauge;

    return $metrics;
  }

}
