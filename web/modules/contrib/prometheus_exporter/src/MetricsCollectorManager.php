<?php

namespace Drupal\prometheus_exporter;

/**
 * Collects metrics for export to prometheus.
 */
class MetricsCollectorManager {

  /**
   * The metrics collector plugin collection.
   *
   * @var \Drupal\prometheus_exporter\MetricsCollectorPluginCollection
   */
  protected $pluginCollection;

  /**
   * MetricsCollectorCollector constructor.
   *
   * @param \Drupal\prometheus_exporter\MetricsCollectorPluginManager $pluginManager
   *   The plugin manager.
   */
  public function __construct(MetricsCollectorPluginManager $pluginManager) {
    $this->pluginCollection = new MetricsCollectorPluginCollection($pluginManager, $pluginManager->getDefinitions());
  }

  /**
   * {@inheritdoc}
   */
  public function collectMetrics() {
    $metrics = [];
    /** @var \Drupal\prometheus_exporter\Plugin\MetricsCollectorInterface $collector */
    foreach ($this->pluginCollection->getIterator() as $collector) {
      if ($collector->isEnabled() && $collector->applies()) {
        $metrics = array_merge($metrics, $collector->collectMetrics());
      }
    }
    return $metrics;
  }

}
