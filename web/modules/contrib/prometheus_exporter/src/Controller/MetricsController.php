<?php

namespace Drupal\prometheus_exporter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\prometheus_exporter\MetricsCollectorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * A controller for exporting prometheus metrics.
 */
class MetricsController extends ControllerBase {

  /**
   * The metric serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The metrics collector.
   *
   * @var \Drupal\prometheus_exporter\MetricsCollectorManager
   */
  protected $metricsCollector;

  /**
   * MetricsController constructor.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The metrics serializer.
   * @param \Drupal\prometheus_exporter\MetricsCollectorManager $metricsCollector
   *   The metrics collector.
   */
  public function __construct(SerializerInterface $serializer, MetricsCollectorManager $metricsCollector) {
    $this->serializer = $serializer;
    $this->metricsCollector = $metricsCollector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('prometheus_exporter.serializer'),
      $container->get('prometheus_exporter.metrics_collector_manager')
    );
  }

  /**
   * Handles metrics requests.
   */
  public function metrics() {
    $metrics = $this->metricsCollector->collectMetrics();
    $output = [];
    foreach ($metrics as $metric) {
      $output[] = $this->serializer->serialize($metric, 'prometheus');
    }

    $response = new Response();
    $response->setMaxAge(0);
    $response->headers->set('Content-Type', 'text/plain; version=0.0.4');
    $response->setContent(implode($output));
    return $response;
  }

}
