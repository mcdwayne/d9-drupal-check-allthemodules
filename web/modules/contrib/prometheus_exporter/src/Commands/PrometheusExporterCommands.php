<?php

namespace Drupal\prometheus_exporter\Commands;

use Drupal\prometheus_exporter\MetricsCollectorManager;
use Drush\Commands\DrushCommands;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * A Drush commandfile for Prometheus Exporter.
 */
class PrometheusExporterCommands extends DrushCommands {

  /**
   * The metrics collector manager.
   *
   * @var \Drupal\prometheus_exporter\MetricsCollectorManager
   */
  protected $metricsCollectorManager;

  /**
   * The prometheus serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * PrometheusExporterCommands constructor.
   *
   * @param \Drupal\prometheus_exporter\MetricsCollectorManager $metricsCollectorManager
   *   The metrics collector manager.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The prometheus serializer.
   */
  public function __construct(MetricsCollectorManager $metricsCollectorManager, SerializerInterface $serializer) {
    parent::__construct();
    $this->metricsCollectorManager = $metricsCollectorManager;
    $this->serializer = $serializer;
  }

  /**
   * Export prometheus metrics.
   *
   * @usage prometheus:export
   *   Export prometheus metrics.
   *
   * @command prometheus:export
   */
  public function export() {
    $metrics = $this->metricsCollectorManager->collectMetrics();
    $output = [];
    foreach ($metrics as $metric) {
      $output[] = $this->serializer->serialize($metric, 'prometheus');
    }
    $this->io()->write($output);
  }

}
